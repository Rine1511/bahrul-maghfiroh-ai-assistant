<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use App\Models\ChatLog;
use App\Services\KnowledgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected ChatbotService $gemini;
    protected KnowledgeService $knowledge;

    public function __construct(
        ChatbotService $gemini,
        KnowledgeService $knowledge
    ) {
        $this->gemini = $gemini;
        $this->knowledge = $knowledge;
    }

    /**
     * Menampilkan halaman chatbot.
     */
    public function index()
    {
        $history = session('chat_history', []);

        return view('chatbot.index', compact('history'));
    }

    /**
     * Memproses pertanyaan dan memberikan jawaban.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'sid' => ['nullable', 'string', 'max:64'],
        ]);

        $message = trim($data['message']);

        try {
            // Mencari informasi relevan dari database.
            $knowledge = $this->knowledge->search($message);

            $contextText = '';

            if ($knowledge->isNotEmpty()) {
                foreach ($knowledge as $item) {
                    $typeLabel = match ($item->source_type ?? 'knowledge') {
                        'faq' => 'FAQ Resmi',
                        'website' => 'Halaman Website',
                        'sib_bm' => 'Data Fasilitas SIB-BM',
                        default => 'Informasi Pondok',
                    };

                    $contextText .= "--- [{$typeLabel}: {$item->title}] ---\n";
                    $contextText .= "{$item->content}\n\n";
                }
            } else {
                $contextText = "(Tidak ada data spesifik yang cocok dari basis data pondok.)\n";
            }

            // Mengambil riwayat percakapan.
            $history = session('chat_history', []);

            $historyText = '';

            foreach (array_slice($history, -10) as $item) {
                $role = ($item['role'] ?? '') === 'user'
                    ? 'Pengguna'
                    : 'Asisten';

                $content = trim((string) ($item['content'] ?? ''));

                if ($content !== '') {
                    $historyText .= "{$role}: {$content}\n";
                }
            }

            // Menyusun prompt untuk Gemini.
            $prompt = "
Kamu adalah Bahrul Maghfiroh AI Assistant, asisten virtual resmi Pondok Pesantren Bahrul Maghfiroh Malang.

TUGAS UTAMA:
Kamu hanya bertugas memberikan informasi dan menjawab pertanyaan yang berkaitan dengan Pondok Pesantren Bahrul Maghfiroh Malang.

GAYA KOMUNIKASI:
- Gunakan bahasa Indonesia yang sopan, ramah, dan mudah dipahami.
- Berikan jawaban secara singkat, jelas, dan informatif.
- Gunakan sapaan yang santun.
- Jangan memberikan informasi yang tidak memiliki dasar.

PEDOMAN MENJAWAB:

1. PERTANYAAN SEPUTAR PONDOK:
- Jawab hanya berdasarkan informasi resmi pondok yang diberikan.
- Jangan mengarang informasi, biaya, tanggal, alamat, nomor kontak, atau fakta lainnya.
- Jika informasi yang ditanyakan belum tersedia, sampaikan bahwa informasi tersebut belum tersedia dalam sistem dan sarankan pengguna menghubungi pihak pondok secara langsung.

2. SALAM DAN SAPAAN:
- Kamu diperbolehkan membalas salam, sapaan, ucapan terima kasih, dan percakapan singkat secara ramah.
- Arahkan percakapan kembali pada informasi Pondok Pesantren Bahrul Maghfiroh.

3. PERTANYAAN DI LUAR PONDOK:
- Jangan menjawab pertanyaan umum yang tidak berkaitan dengan Pondok Pesantren Bahrul Maghfiroh.
- Sampaikan dengan sopan bahwa kamu hanya dapat membantu memberikan informasi seputar Pondok Pesantren Bahrul Maghfiroh Malang.

INFORMASI RESMI PONDOK:
{$contextText}

RIWAYAT PERCAKAPAN:
{$historyText}

PERTANYAAN PENGGUNA:
{$message}
";

            // Meminta jawaban dari Gemini.
            $answer = $this->gemini->generate($prompt);

            if (!is_string($answer) || trim($answer) === '') {
                throw new \RuntimeException('Gemini tidak menghasilkan jawaban.');
            }

            $answer = trim($answer);

            // Menyimpan riwayat percakapan ke session.
            $history[] = [
                'role' => 'user',
                'content' => $message,
            ];

            $history[] = [
                'role' => 'assistant',
                'content' => $answer,
            ];

            // Membatasi riwayat agar session tidak terus membesar.
            $history = array_slice($history, -20);

            session(['chat_history' => $history]);

            // Menentukan sumber informasi.
            $source = $knowledge->isNotEmpty() ? 'local_ai' : 'ai';

            // Menyimpan percakapan ke database.
            ChatLog::create([
                'session_id' => $data['sid']
                    ?? substr(sha1((string) $request->ip()), 0, 40),
                'question' => $message,
                'answer' => $answer,
                'source' => $source,
                'answered' => true,
            ]);

            return response()->json([
                'success' => true,
                'answer' => $answer,
                'source' => $source,
            ]);

        } catch (\Throwable $e) {
            Log::error('Chatbot sendMessage error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Maaf, saat ini layanan chatbot sedang mengalami kendala. Silakan coba kembali beberapa saat lagi.',
            ], 500);
        }
    }

    /**
     * Mereset riwayat percakapan.
     */
    public function reset(): JsonResponse
    {
        session()->forget('chat_history');

        return response()->json([
            'success' => true,
            'message' => 'Percakapan berhasil direset.',
        ]);
    }

    /**
     * Menyediakan widget chatbot dalam bentuk JavaScript.
     */
    public function embed(): Response
    {
        $dir = app_path('Chatbot/Resources/assets/');

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        $config = [
            'name' => config('chatbot.name'),
            'subtitle' => config('chatbot.subtitle'),
            'launcher' => config('chatbot.launcher'),
            'greeting' => config('chatbot.greeting'),
            'quick' => config('chatbot.quick_replies'),
        ];

        $cssPath = $dir . 'widget.css';
        $jsPath = $dir . 'widget.js';

        if (!file_exists($cssPath) || !file_exists($jsPath)) {
            abort(404, 'File widget chatbot tidak ditemukan.');
        }

        $body = '(function(){var CSS='
            . json_encode(file_get_contents($cssPath), $flags)
            . ',CONFIG=' . json_encode($config, $flags) . ';'
            . file_get_contents($jsPath)
            . "\n})();";

        return response($body, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
