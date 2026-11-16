<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('chatbot_knowledge', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('category', 100)->nullable();
        $table->longText('content');
        $table->json('keywords')->nullable();
        $table->string('source_url', 500)->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge');
    }
};
