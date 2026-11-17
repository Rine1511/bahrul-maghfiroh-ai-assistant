<?php

return [
    'ai_enabled' => env('CHATBOT_AI_ENABLED', true),

    'gemini_key' => env('GEMINI_API_KEY'),

    'ai_model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),

    'min_score' => env('CHATBOT_MIN_SCORE', 3),

    'fallback' => 'Maaf, informasi tersebut belum tersedia. Silakan hubungi pihak Pondok Pesantren Bahrul Maghfiroh untuk informasi lebih lanjut.',

    'off_topic' => 'Maaf, saya hanya dapat membantu menjawab pertanyaan seputar Pondok Pesantren Bahrul Maghfiroh.',

    'topic_words' => [
        'pondok',
        'pesantren',
        'bahrul',
        'maghfiroh',
        'santri',
        'pendidikan',
        'gedung',
        'asrama',
        'fasilitas',
        'alamat',
        'sekolah',
        'masjid',
    ],

    'contact' => [
        'alamat' => env('CHATBOT_ALAMAT', ''),
        'website' => env('CHATBOT_WEBSITE', ''),
    ],
];