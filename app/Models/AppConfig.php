<?php

namespace App\Models;

use App\Services\AppConfigService;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AppConfig extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['key', 'value', 'description', 'media_path', 'required'];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }

    /**
     * Qualquer gravação/remoção limpa o cache da chave (e da chave antiga, se
     * renomeada) — não só pela tela de Configurações, mas também por seeder,
     * tinker ou migration. Senão o valor antigo ficava em cache.
     */
    protected static function booted(): void
    {
        $limpar = function (AppConfig $config): void {
            $service = app(AppConfigService::class);
            $service->forget($config->key);

            if ($config->getOriginal('key') && $config->getOriginal('key') !== $config->key) {
                $service->forget($config->getOriginal('key'));
            }
        };

        static::saved($limpar);
        static::deleted($limpar);
    }
}
