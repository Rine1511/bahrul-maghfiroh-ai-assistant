<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\ChatbotController;

Route::get('/chatbot', [ChatbotController::class, 'index'])
    ->name('chatbot.index');

Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage'])
    ->name('chatbot.message');

Route::post('/chatbot/reset', [ChatbotController::class, 'reset'])
    ->name('chatbot.reset');