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
    Schema::create('chatbot_sources', function (Blueprint $table) {
        $table->id();
        $table->string('source_type', 50);
        $table->string('external_id', 100)->nullable();
        $table->string('title');
        $table->string('url', 500)->nullable();
        $table->longText('content');
        $table->string('checksum', 64)->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamp('last_synced_at')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_sources');
    }
};
