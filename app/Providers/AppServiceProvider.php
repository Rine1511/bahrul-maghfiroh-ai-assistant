<?php

namespace App\Providers;

use App\Chatbot\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Memuat konfigurasi chatbot.
        $this->mergeConfigFrom(
            app_path('Chatbot/config.php'),
            'chatbot'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Memuat routes chatbot.
        $this->loadRoutesFrom(
            app_path('Chatbot/routes.php')
        );

        // Memuat migration chatbot.
        $this->loadMigrationsFrom(
            app_path('Chatbot/Database/Migrations')
        );

        // Mendaftarkan command chatbot.
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
