<?php

use App\Chatbot\Services\ChatbotService;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Chatbot\Services\ChatbotService::class,
];
