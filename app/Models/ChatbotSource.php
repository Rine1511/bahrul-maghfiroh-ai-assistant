<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSource extends Model
{
    protected $table = 'chatbot_sources';

    protected $fillable = [
        'source_type',
        'external_id',
        'title',
        'url',
        'content',
        'checksum',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}