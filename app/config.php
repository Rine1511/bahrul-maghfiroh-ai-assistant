<?php

return [
    // Lokasi folder chatbot (dipakai untuk memuat CSS dan JS).
    'path' => __DIR__,

    'name'     => 'Asisten Bahrul Maghfiroh',
    'subtitle' => 'Pondok Pesantren Bahrul Maghfiroh, Kota Malang',
    'launcher' => 'Tanya pondok',

    // Website yang boleh memasang chatbot ini. '*' = semua website (cocok untuk uji coba).
    // Setelah live, isi domain website pondok, pisahkan koma jika lebih dari satu.
    // Contoh: https://www.domain-pondok.com,https://domain-pondok.com
    'allowed_origins' => env('CHATBOT_ALLOWED_ORIGINS', '*'),

    // Skor minimal agar jawaban dari basis pengetahuan lokal dianggap cocok.
    'min_score' => (int) env('CHATBOT_MIN_SCORE', 3),

    // Mode AI (opsional). Jika aktif, pertanyaan yang tidak cocok dengan
    // basis pengetahuan lokal akan dijawab Claude berdasarkan data yang sama.
    'ai_enabled'    => (bool) env('CHATBOT_AI_ENABLED', false),
    'anthropic_key' => env('ANTHROPIC_API_KEY'),
    'ai_model'      => env('CHATBOT_AI_MODEL', 'claude-haiku-5-5'),

    // Dipakai untuk mengganti {{alamat}} dan {{website}} di jawaban.
    'contact' => [
        'alamat'  => env('CHATBOT_ALAMAT', 'Jl. Joyoagung Atas No. 02, Tlogomas, Lowokwaru, Kota Malang, Jawa Timur'),
        'website' => env('APP_URL', 'http://localhost'),
    ],

    'greeting' => 'Assalamualaikum! Saya asisten virtual Pondok Pesantren Bahrul Maghfiroh Kota Malang. Ada yang ingin Anda ketahui?',

    // Dijawab jika pertanyaan sama sekali tidak berkaitan dengan pondok (contoh: "apa ibukota Indonesia?").
    'off_topic' => 'Maaf, saya tidak bisa menjawab pertanyaan di luar topik. Silakan tanyakan seputar Pondok Pesantren Bahrul Maghfiroh, misalnya lokasi, pengasuh, pendidikan, atau unit usaha.',

    // Kata yang menandakan pertanyaan masih seputar pondok (dipakai saat mode AI mati).
    'topic_words' => [
        'pondok', 'pesantren', 'ponpes', 'santri', 'santriwati', 'bahrul', 'maghfiroh', 'mahfiroh',
        'ustadz', 'ustadzah', 'kiai', 'kyai', 'gus', 'asrama', 'ngaji', 'mengaji', 'tahfidz',
        'madrasah', 'yayasan', 'pengasuh', 'alumni', 'sma',
    ],

    'fallback' => 'Maaf, saya belum punya informasi yang pas untuk pertanyaan itu. Silakan datang langsung ke pondok atau kunjungi {{website}} untuk informasi lebih lanjut.',

    'quick_replies' => [
        'Di mana lokasi pondok?',
        'Siapa pengasuh pondok?',
        'Apa saja unit usaha santri?',
        'Bagaimana cara mendaftar?',
    ],
];
