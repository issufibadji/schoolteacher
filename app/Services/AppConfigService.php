<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppConfigService
{
    /**
     * Valor da config, ou $default se não existir/estiver vazio. Guarda '' no
     * cache quando não há valor (e nunca o default, que varia por chamada).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(
            "app_config.{$key}",
            fn () => AppConfig::query()->where('key', $key)->value('value') ?? '',
        );

        return $value === '' ? $default : $value;
    }

    /**
     * URL pública da mídia da config, ou null se não houver arquivo.
     * Guarda '' no cache quando não há mídia, pra não consultar o banco a cada request.
     */
    public function media(string $key): ?string
    {
        $path = Cache::rememberForever(
            "app_config_media.{$key}",
            fn () => AppConfig::query()->where('key', $key)->value('media_path') ?? '',
        );

        return $path === '' ? null : asset('storage/'.$path);
    }

    public function all(): Collection
    {
        return Cache::rememberForever(
            'app_config.all',
            fn () => AppConfig::query()->orderBy('key')->get(),
        );
    }

    public function forget(string $key): void
    {
        Cache::forget("app_config.{$key}");
        Cache::forget("app_config_media.{$key}");
        Cache::forget('app_config.all');
    }
}
