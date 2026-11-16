<?php

namespace App\Services;

use App\Models\ChatbotKnowledge;

class KnowledgeService
{
    public function search(string $question, int $limit = 5)
    {
        $words = preg_split('/\s+/', strtolower(trim($question)));

        $words = array_filter($words, function ($word) {
            return strlen($word) >= 3;
        });

        if (empty($words)) {
            return collect();
        }

        return ChatbotKnowledge::where('is_active', true)
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('title', 'LIKE', "%{$word}%")
                        ->orWhere('content', 'LIKE', "%{$word}%");
                }
            })
            ->limit($limit)
            ->get();
    }
}