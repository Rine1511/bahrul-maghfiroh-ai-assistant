<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generate(string $prompt): string
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model');

        $response = Http::connectTimeout(15)
            ->timeout(60)
            ->retry(3, 2000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || (
                        $exception instanceof \Illuminate\Http\Client\RequestException
                        && in_array($exception->response->status(), [429, 500, 502, 503, 504])
                    );
            })
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
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                    ],
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
