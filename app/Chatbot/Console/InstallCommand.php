<?php

namespace App\Chatbot\Console;

use Database\Seeders\ChatbotKnowledgeSeeder;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'chatbot:install';

    protected $description = 'Membuat tabel chatbot dan mengisi data pondok (aman dijalankan ulang)';

    public function handle(): int
    {
        // Menjalankan migrasi database
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->error('Migrasi database gagal dijalankan.');

            return self::FAILURE;
        }

        // Mengisi data awal chatbot
        if (
            $this->call('db:seed', [
                '--class' => ChatbotKnowledgeSeeder::class,
                '--force' => true,
            ]) !== self::SUCCESS
        ) {
            $this->error('Seeder chatbot gagal dijalankan.');

            return self::FAILURE;
        }

        // Membersihkan cache
        $this->call('cache:clear');

        $this->info('Chatbot siap dipakai.');

        return self::SUCCESS;
    }
}