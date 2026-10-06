<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // `php artisan serve` só repassa algumas variáveis de ambiente ao servidor PHP.
        // Sem TEMP/TMP, no Windows o PHP tenta gravar uploads em C:\Windows e falha
        // ("unable to create a temporary file" → 422 "failed to upload"). Só afeta o
        // servidor de desenvolvimento; produção (nginx + PHP-FPM) não usa isso.
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables = array_values(array_unique([
                ...ServeCommand::$passthroughVariables, 'TEMP', 'TMP',
            ]));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
