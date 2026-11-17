<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotKnowledge extends Model
{
    protected $table = 'chatbot_knowledge';

    protected $fillable = [
        'title',
        'category',
        'content',
        'keywords',
        'source_type',
        'source_url',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];
}
