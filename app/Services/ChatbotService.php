<?php

namespace App\Chatbot\Services;

use App\Chatbot\Models\ChatKnowledge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private const OFF_TOPIC_TAG = '[LUAR_TOPIK]';

    private const STOPWORDS = [
        'apa', 'apakah', 'yang', 'di', 'ke', 'dari', 'dan', 'atau', 'itu', 'ini', 'ada',
        'saya', 'aku', 'kak', 'min', 'bisa', 'mau', 'tolong', 'dong', 'ya', 'nya', 'untuk',
        'dengan', 'pada', 'adalah', 'bagaimana', 'gimana', 'berapa', 'siapa', 'kapan',
    ];

    /** @return array{answer:string, source:string, answered:bool} */
    public function reply(string $message, array $history = []): array
    {
        $clean   = $this->normalize($message);
        $entries = $this->knowledge();

        [$best, $score] = $this->findBest($clean, $entries);

        if ($best && $score >= config('chatbot.min_score')) {
            return $this->result($best->answer, 'local', true);
        }

        if (config('chatbot.ai_enabled') && config('chatbot.anthropic_key')) {
            $ai = $this->askClaude($message, $history, $entries);
            if ($ai !== null) {
                if (str_contains($ai, self::OFF_TOPIC_TAG)) {
                    return $this->offTopic();
                }
                return $this->result($ai, 'ai', true);
            }
        }

        // Tanpa AI: pertanyaan yang tidak menyebut hal seputar pondok dianggap di luar topik.
        // Pesan satu kata (misalnya "ya", "oke") tidak dianggap luar topik.
        if (!$this->looksOnTopic($clean) && count(explode(' ', $clean)) > 1) {
            return $this->offTopic();
        }

        return $this->result(config('chatbot.fallback'), 'fallback', false);
    }

    private function looksOnTopic(string $clean): bool
    {
        $words = explode(' ', $clean);

        return count(array_intersect($words, config('chatbot.topic_words', []))) > 0;
    }

    /** Dicatat sebagai terjawab agar daftar "belum terjawab" hanya berisi pertanyaan seputar pondok. */
    private function offTopic(): array
    {
        return $this->result(config('chatbot.off_topic'), 'off_topic', true);
    }

    // ---------------------------------------------------------------- lokal

    private function knowledge()
    {
        return Cache::remember('chat_knowledge_active', 300, fn () =>
            ChatbotKnowledge::where('is_active', true)->get()
        );
    }

    private function findBest(string $clean, $entries): array
    {
        $padded = ' ' . $clean . ' ';
        $tokens = array_diff(explode(' ', $clean), self::STOPWORDS);
        $best = null;
        $bestScore = 0;

        foreach ($entries as $entry) {
            $score = 0;

            foreach (explode(',', $entry->keywords) as $kw) {
                $kw = $this->normalize($kw);
                if ($kw === '') {
                    continue;
                }
                if (str_contains($padded, ' ' . $kw . ' ')) {
                    // frasa yang lebih panjang = lebih spesifik
                    $score += 2 + str_word_count($kw);
                }
            }

            // sedikit bobot dari kata pada judul entri
            $titleTokens = array_diff(explode(' ', $this->normalize($entry->title)), self::STOPWORDS);
            $score += count(array_intersect($tokens, $titleTokens));

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry;
            }
        }

        // Sapaan hanya dianggap cocok jika pesannya memang sapaan
        // (agar "halo, apa ibukota Indonesia?" tidak dijawab dengan salam).
        if ($best && $best->category === 'umum') {
            $greetingWords = explode(' ', $this->normalize(str_replace(',', ' ', $best->keywords)));
            $extra = array_diff($tokens, $greetingWords, ['tanya', 'bertanya', 'wr', 'wb', 'warahmatullahi', 'wabarakatuh']);
            if (count($extra) > 1) {
                return [null, 0];
            }
        }

        return [$best, $bestScore];
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    // ------------------------------------------------------------------ AI

    private function askClaude(string $message, array $history, $entries): ?string
    {
        $kb = $entries->map(fn ($e) => "## {$e->title}\n" . $this->fill($e->answer))->implode("\n\n");
        $alamat = $this->contact('alamat');

        $system = <<<TXT
Anda adalah asisten virtual resmi Pondok Pesantren Bahrul Maghfiroh Kota Malang di website pondok.
Jawab dalam Bahasa Indonesia yang sopan, hangat, dan ringkas (maksimal sekitar 4 kalimat).
Jawab HANYA berdasarkan basis pengetahuan di bawah. Jangan mengarang fakta, biaya, tanggal, atau nomor kontak.
Jika informasinya tidak ada, katakan terus terang dan sarankan pengguna datang langsung ke pondok di {$alamat}.
Jika pertanyaan sama sekali tidak berkaitan dengan Pondok Pesantren Bahrul Maghfiroh (misalnya pengetahuan umum, politik, matematika, pemrograman, atau hiburan), balas HANYA dengan teks persis: [LUAR_TOPIK]

# Basis pengetahuan
{$kb}
TXT;

        try {
            $res = Http::withHeaders([
                'x-api-key'         => config('chatbot.anthropic_key'),
                'anthropic-version' => '2023-06-01',
            ])->timeout(20)->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('chatbot.ai_model'),
                'max_tokens' => 500,
                'system'     => $system,
                'messages'   => $this->buildMessages($message, $history),
            ]);

            if ($res->successful()) {
                $text = trim((string) $res->json('content.0.text'));
                return $text !== '' ? $text : null;
            }

            Log::warning('Chatbot AI gagal', ['status' => $res->status(), 'body' => $res->body()]);
        } catch (\Throwable $e) {
            Log::warning('Chatbot AI error: ' . $e->getMessage());
        }

        return null;
    }

    /** Pastikan pesan berselang-seling user/assistant dan diawali user. */
    private function buildMessages(string $message, array $history): array
    {
        $out = [];

        foreach (array_slice($history, -6) as $h) {
            $role = $h['role'] ?? null;
            $content = trim((string) ($h['content'] ?? ''));
            if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }
            if (empty($out) && $role === 'assistant') {
                continue;
            }
            if (!empty($out) && end($out)['role'] === $role) {
                $out[count($out) - 1]['content'] = $content;
                continue;
            }
            $out[] = ['role' => $role, 'content' => $content];
        }

        if (!empty($out) && end($out)['role'] === 'user') {
            array_pop($out);
        }
        $out[] = ['role' => 'user', 'content' => $message];

        return $out;
    }

    // -------------------------------------------------------------- util

    private function result(string $answer, string $source, bool $answered): array
    {
        return ['answer' => $this->fill($answer), 'source' => $source, 'answered' => $answered];
    }

    private function contact(string $key): string
    {
        return (string) config("chatbot.contact.$key");
    }

    private function fill(string $text): string
    {
        return strtr($text, [
            '{{alamat}}'  => $this->contact('alamat'),
            '{{website}}' => $this->contact('website'),
        ]);
    }
}
