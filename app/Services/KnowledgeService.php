<?php

namespace App\Services;

use App\Models\ChatbotFaq;
use App\Models\ChatbotKnowledge;

class KnowledgeService
{
    public function search(string $question, int $limit = 5)
    {
        // Memecah pertanyaan menjadi kata kunci
        $words = preg_split(
            '/[^\p{L}\p{N}]+/u',
            mb_strtolower(trim($question)),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $words = array_values(array_unique(array_filter(
            $words,
            fn ($word) => mb_strlen($word) >= 3
        )));

        if (empty($words)) {
            return collect();
        }

        // Mencari informasi dari tabel FAQ
        $faqs = ChatbotFaq::where('is_active', true)
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('question', 'LIKE', "%{$word}%")
                        ->orWhere('answer', 'LIKE', "%{$word}%")
                        ->orWhereJsonContains('keywords', $word);
                }
            })
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (object) [
                    'title' => 'FAQ: ' . $item->question,
                    'content' => $item->answer,
                ];
            });

        // Mencari informasi dari tabel knowledge
        $knowledge = ChatbotKnowledge::where('is_active', true)
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('title', 'LIKE', "%{$word}%")
                        ->orWhere('content', 'LIKE', "%{$word}%")
                        ->orWhereJsonContains('keywords', $word);
                }
            })
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (object) [
                    'title' => $item->title,
                    'content' => $item->content,
                ];
            });

        // Menggabungkan hasil pencarian
        return $faqs->concat($knowledge)->take($limit);
    }
}
