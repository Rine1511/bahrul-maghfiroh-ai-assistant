<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class GeminiController extends Controller
{
    public function test()
    {
        $response = Http::connectTimeout(15)
            ->timeout(60)
            ->withHeaders([
                'x-goog-api-key' => config('services.gemini.key'),
            ])
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/'
                . config('services.gemini.model')
                . ':generateContent',
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => 'Halo Gemini, perkenalkan dirimu.'
                                ]
                            ]
                        ]
                    ]
                ]
            );

        if ($response->failed()) {
            return response()->json([
                'error' => $response->json()
            ], 500);
        }

        return response()->json([
            'jawaban' => $response->json(
                'candidates.0.content.parts.0.text'
            )
        ]);
    }
}
