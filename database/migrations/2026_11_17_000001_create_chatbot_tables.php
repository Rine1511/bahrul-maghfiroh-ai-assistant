<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('title');
            $table->text('keywords');          // dipisah koma, contoh: "lokasi, alamat, di mana"
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->text('question');
            $table->text('answer');
            $table->string('source', 10);      // local | ai | fallback
            $table->boolean('answered')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
        Schema::dropIfExists('chat_knowledge');
    }
};
