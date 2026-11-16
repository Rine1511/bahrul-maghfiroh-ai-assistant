<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncWebsiteKnowledge extends Command
{
    protected $signature = 'app:sync-website-knowledge';

    protected $description = 'Sinkronisasi informasi website pondok';

    public function handle()
    {
        $this->info('Fitur sinkronisasi website belum diaktifkan.');

        return self::SUCCESS;
    }
}