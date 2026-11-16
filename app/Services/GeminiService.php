<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generate(string $prompt): string
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model');

        $response = Http::timeout(60)
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            );

        $response->throw();

        return data_get(
            $response->json(),
            'candidates.0.content.parts.0.text',
            ''
        );
    }
}