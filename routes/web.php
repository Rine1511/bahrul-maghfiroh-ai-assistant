<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\GeminiController;

Route::get('/chatbot-test', [GeminiController::class, 'test']);

use App\Models\ChatbotKnowledge;

Route::get('/test-knowledge', function () {
    ChatbotKnowledge::create([
        'title' => 'Profil Pondok Pesantren Bahrul Maghfiroh',
        'category' => 'Profil',
        'content' => 'Pondok Pesantren Bahrul Maghfiroh merupakan lembaga pendidikan Islam yang memadukan pendidikan pesantren salaf dengan pendidikan umum.',
        'keywords' => ['profil', 'pondok', 'pesantren', 'bahrul maghfiroh'],
        'is_active' => true,
    ]);

    return 'Data pengetahuan berhasil ditambahkan.';
});

use App\Http\Controllers\ChatbotController;

Route::get('/chatbot', [ChatbotController::class, 'index'])
    ->name('chatbot.index');

Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage'])
    ->name('chatbot.message');

Route::post('/chatbot/reset', [ChatbotController::class, 'reset'])
    ->name('chatbot.reset');