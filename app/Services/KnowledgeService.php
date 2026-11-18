<?php

namespace App\Services;

use App\Models\ChatbotFaq;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotSource;
use Illuminate\Support\Collection;

class KnowledgeService
{
    protected int $maxContextLength = 2500;

    protected array $stopwords = [
        'ada',
        'adalah',
        'adanya',
        'adapun',
        'agar',
        'akan',
        'aku',
        'anda',
        'antara',
        'apa',
        'apabila',
        'apakah',
        'atau',
        'awal',
        'bagaimana',
        'bagi',
        'bahkan',
        'bahwa',
        'baik',
        'banyak',
        'beberapa',
        'begitu',
        'belum',
        'berapa',
        'berikut',
        'bisa',
        'boleh',
        'buat',
        'bukan',
        'cukup',
        'dalam',
        'dan',
        'dapat',
        'dari',
        'dengan',
        'depan',
        'di',
        'dia',
        'dulu',
        'guna',
        'hanya',
        'harus',
        'hingga',
        'ia',
        'ialah',
        'ingin',
        'ini',
        'itu',
        'jadi',
        'jika',
        'juga',
        'kalau',
        'kami',
        'kamu',
        'karena',
        'ke',
        'kepada',
        'ketika',
        'kita',
        'kurang',
        'lagi',
        'lain',
        'lebih',
        'maka',
        'mana',
        'masih',
        'mau',
        'mereka',
        'merupakan',
        'misalnya',
        'mohon',
        'namun',
        'oleh',
        'pada',
        'para',
        'pernah',
        'pula',
        'saat',
        'saja',
        'sama',
        'sangat',
        'saya',
        'sebagai',
        'sebab',
        'sebelum',
        'secara',
        'sedang',
        'sehingga',
        'sejak',
        'sekali',
        'selain',
        'selalu',
        'selama',
        'semua',
        'seperti',
        'serta',
        'setelah',
        'setiap',
        'siapa',
        'sudah',
        'supaya',
        'tadi',
        'tanpa',
        'tapi',
        'telah',
        'tentang',
        'terhadap',
        'termasuk',
        'tersebut',
        'tidak',
        'untuk',
        'walaupun',
        'yang',
        'ya',
        'yaitu',
        'yakni',
    ];

    /**
     * Mencari informasi relevan dari tiga sumber database.
     */
    public function search(string $question, int $limit = 5): Collection
    {
        $normalizedQuestion = $this->normalizeText($question);
        $keywords = $this->extractKeywords($normalizedQuestion);

        if (empty($keywords)) {
            return collect();
        }

        $limit = max(1, min($limit, 10));

        $results = collect()
            ->concat($this->searchFaqs($normalizedQuestion, $keywords))
            ->concat($this->searchKnowledge($normalizedQuestion, $keywords))
            ->concat($this->searchSources($normalizedQuestion, $keywords));

        $results = $results
            ->filter(fn($item) => $item->score >= $item->minimum_score)
            ->sortByDesc(function ($item) use ($normalizedQuestion) {
                $isFaq = $item->source_type === 'faq';

                $exactFaqMatch = $isFaq
                    && $this->containsPhrase(
                        $normalizedQuestion,
                        $this->normalizeText(str_replace('FAQ: ', '', $item->title))
                    );

                return ($exactFaqMatch ? 10000 : 0) + $item->score;
            })
            ->values();

        return $this->applyContextBudget($results, $limit);
    }

    /**
     * Pencarian FAQ.
     */
    protected function searchFaqs(
        string $question,
        array $keywords
    ): Collection {
        $faqs = ChatbotFaq::where('is_active', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhere('question', 'LIKE', "%{$word}%")
                        ->orWhere('answer', 'LIKE', "%{$word}%")
                        ->orWhereJsonContains('keywords', $word);
                }
            })
            ->get();

        return $faqs->map(function ($faq) use ($question, $keywords) {
            $title = $this->normalizeText($faq->question);
            $answer = $this->normalizeText($faq->answer);

            $score = 0;
            $matchedCount = 0;

            if ($title === $question) {
                $score += 160;
            } elseif ($this->containsPhrase($question, $title)) {
                $score += 90;
            }

            $faqKeywords = $this->normalizeKeywords($faq->keywords);

            foreach ($keywords as $word) {
                $matched = false;

                if (in_array($word, $faqKeywords, true)) {
                    $score += 35;
                    $matched = true;
                }

                if ($this->containsWord($title, $word)) {
                    $score += 25;
                    $matched = true;
                }

                if ($this->containsWord($answer, $word)) {
                    $score += 8;
                    $matched = true;
                }

                if ($matched) {
                    $matchedCount++;
                }
            }

            $score += $this->coverageBonus($matchedCount, $keywords);

            return (object) [
                'id' => $faq->id,
                'title' => 'FAQ: ' . $faq->question,
                'content' => $faq->answer,
                'source_type' => 'faq',
                'source_url' => null,
                'score' => $score,
                'minimum_score' => 35,
            ];
        });
    }

    /**
     * Pencarian informasi knowledge.
     */
    protected function searchKnowledge(
        string $question,
        array $keywords
    ): Collection {
        $items = ChatbotKnowledge::where('is_active', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhere('title', 'LIKE', "%{$word}%")
                        ->orWhere('content', 'LIKE', "%{$word}%")
                        ->orWhereJsonContains('keywords', $word);
                }
            })
            ->get();

        return $items->map(function ($item) use ($question, $keywords) {
            $title = $this->normalizeText($item->title);
            $content = $this->normalizeText($item->content);

            $score = 0;
            $matchedCount = 0;

            if ($this->containsPhrase($question, $title)) {
                $score += 50;
            }

            $itemKeywords = $this->normalizeKeywords($item->keywords);

            foreach ($keywords as $word) {
                $matched = false;

                if (in_array($word, $itemKeywords, true)) {
                    $score += 30;
                    $matched = true;
                }

                if ($this->containsWord($title, $word)) {
                    $score += 20;
                    $matched = true;
                }

                if ($this->containsWord($content, $word)) {
                    $occurrences = $this->countWord($content, $word);
                    $score += min(24, $occurrences * 8);
                    $matched = true;
                }

                if ($matched) {
                    $matchedCount++;
                }
            }

            $score += $this->coverageBonus($matchedCount, $keywords);

            return (object) [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'source_type' => 'knowledge',
                'source_url' => $item->source_url,
                'score' => $score,
                'minimum_score' => 25,
            ];
        });
    }

    /**
     * Pencarian sumber website dan sumber lainnya.
     */
    protected function searchSources(
        string $question,
        array $keywords
    ): Collection {
        $sources = ChatbotSource::where('is_active', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhere('title', 'LIKE', "%{$word}%")
                        ->orWhere('content', 'LIKE', "%{$word}%");
                }
            })
            ->get();

        return $sources->map(function ($source) use ($question, $keywords) {
            $title = $this->normalizeText($source->title);
            $content = $this->normalizeText($source->content);

            $score = 0;
            $matchedCount = 0;

            if ($this->containsPhrase($question, $title)) {
                $score += 40;
            }

            foreach ($keywords as $word) {
                $matched = false;

                if ($this->containsWord($title, $word)) {
                    $score += 18;
                    $matched = true;
                }

                if ($this->containsWord($content, $word)) {
                    $occurrences = $this->countWord($content, $word);
                    $score += min(18, $occurrences * 6);
                    $matched = true;
                }

                if ($matched) {
                    $matchedCount++;
                }
            }

            $score += $this->coverageBonus($matchedCount, $keywords);

            return (object) [
                'id' => $source->id,
                'title' => $source->title,
                'content' => $source->content,
                'source_type' => $source->source_type ?? 'website',
                'source_url' => $source->url,
                'score' => $score,
                'minimum_score' => 22,
            ];
        });
    }

    /**
     * Bonus berdasarkan persentase kata kunci yang cocok.
     */
    protected function coverageBonus(
        int $matchedCount,
        array $keywords
    ): int {
        if (empty($keywords)) {
            return 0;
        }

        return (int) round(
            ($matchedCount / count($keywords)) * 40
        );
    }

    /**
     * Memastikan kata cocok sebagai satu kata utuh.
     */
    protected function containsWord(string $text, string $word): bool
    {
        if ($word === '') {
            return false;
        }

        $pattern = '/(?<![\p{L}\p{N}])'
            . preg_quote($word, '/')
            . '(?![\p{L}\p{N}])/u';

        return preg_match($pattern, $text) === 1;
    }

    /**
     * Memeriksa kecocokan frasa.
     */
    protected function containsPhrase(
        string $question,
        string $title
    ): bool {
        if ($question === '' || $title === '') {
            return false;
        }

        return str_contains($question, $title)
            || str_contains($title, $question);
    }

    /**
     * Menghitung kemunculan kata utuh.
     */
    protected function countWord(string $text, string $word): int
    {
        $pattern = '/(?<![\p{L}\p{N}])'
            . preg_quote($word, '/')
            . '(?![\p{L}\p{N}])/u';

        return preg_match_all($pattern, $text);
    }

    /**
     * Membersihkan kata kunci dari database.
     */
    protected function normalizeKeywords($keywords): array
    {
        if (!is_array($keywords)) {
            return [];
        }

        $normalized = [];

        foreach ($keywords as $keyword) {
            $value = $this->normalizeText((string) $keyword);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Mengatur batas jumlah informasi dan total karakter konteks.
     */
    protected function applyContextBudget(
        Collection $items,
        int $limit
    ): Collection {
        $selected = collect();
        $totalChars = 0;

        foreach ($items->take($limit) as $item) {
            $typeLabel = match ($item->source_type) {
                'faq' => 'FAQ Resmi',
                'website' => 'Halaman Website',
                'sib_bm' => 'Data Fasilitas SIB-BM',
                default => 'Informasi Pondok',
            };

            $header = "--- [{$typeLabel}: {$item->title}] ---\n";
            $separatorLength = 2;

            $remaining = $this->maxContextLength - $totalChars;
            $availableContent = $remaining
                - mb_strlen($header)
                - $separatorLength;

            if ($availableContent <= 0) {
                break;
            }

            $contentLength = mb_strlen($item->content);

            if ($contentLength > $availableContent) {
                if ($availableContent < 150) {
                    break;
                }

                $item->content = mb_substr(
                    $item->content,
                    0,
                    $availableContent - 3
                ) . '...';
            }

            $selected->push($item);

            $totalChars += mb_strlen($header)
                + mb_strlen($item->content)
                + $separatorLength;
        }

        return $selected;
    }

    /**
     * Normalisasi teks.
     */
    protected function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $text = preg_replace(
            '/[^\p{L}\p{N}\s]+/u',
            ' ',
            $text
        );

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Ekstraksi kata kunci dari pertanyaan.
     */
    protected function extractKeywords(string $text): array
    {
        $words = explode(' ', $text);
        $keywords = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 3) {
                continue;
            }

            if (in_array($word, $this->stopwords, true)) {
                continue;
            }

            $keywords[] = $word;
        }

        return array_values(array_unique($keywords));
    }
}
