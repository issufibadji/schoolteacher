<?php

namespace App\Providers;

use App\Services\AppConfigService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        $this->aplicarNomeDoSistema();
    }

    /**
     * O nome editado em Configurações (app_nome) vira o config('app.name'),
     * então sidebar, títulos, login, e-mails e 2FA mudam juntos. Sem banco/
     * tabela ainda (instalação nova, antes do migrate), fica o APP_NAME do .env.
     */
    private function aplicarNomeDoSistema(): void
    {
        try {
            $nome = $this->app->make(AppConfigService::class)->get('app_nome');
        } catch (Throwable) {
            return;
        }

        if ($nome) {
            config(['app.name' => $nome]);
        }
    }
}
