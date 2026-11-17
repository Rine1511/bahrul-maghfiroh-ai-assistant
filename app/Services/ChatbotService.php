<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotService
{
    /**
     * Mengirim prompt ke Gemini dan mengambil jawaban.
     */
    public function generate(string $prompt): string
    {
        $apiKey = config('chatbot.gemini_key');
        $model = config('chatbot.ai_model', 'gemini-3.1-flash-lite');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'Gemini API key belum dikonfigurasi.'
            );
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 700,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Gemini API gagal', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                throw new RuntimeException(
                    'Gemini API gagal dengan status HTTP '
                    . $response->status()
                );
            }

            $answer = $response->json(
                'candidates.0.content.parts.0.text'
            );

            if (!is_string($answer) || trim($answer) === '') {
                Log::warning('Gemini tidak menghasilkan teks jawaban', [
                    'response' => $response->json(),
                ]);

                throw new RuntimeException(
                    'Gemini tidak menghasilkan jawaban.'
                );
            }

            return trim($answer);

        } catch (\Throwable $e) {
            Log::error('ChatbotService Gemini error', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
