<?php

namespace Tests\Feature\Admin;

use App\Models\AppConfig;
use App\Providers\CoreServiceProvider;
use App\Services\AppConfigService;
use Database\Seeders\AppConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppConfigSistemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_keys_and_keeps_admin_edits(): void
    {
        AppConfig::create(['key' => 'login_titulo', 'value' => 'Editado pelo admin']);

        $this->seed(AppConfigSeeder::class);
        $this->seed(AppConfigSeeder::class);

        foreach (['app_nome', 'app_logo', 'app_favicon', 'app_rodape', 'login_imagem', 'login_subtitulo', 'login_titulo', 'webhook_url'] as $key) {
            $this->assertSame(1, AppConfig::where('key', $key)->count(), $key);
        }

        $this->assertSame('Editado pelo admin', AppConfig::where('key', 'login_titulo')->value('value'));
    }

    public function test_app_nome_replaces_the_application_name(): void
    {
        AppConfig::create(['key' => 'app_nome', 'value' => 'English School']);

        (new CoreServiceProvider($this->app))->boot();

        $this->assertSame('English School', config('app.name'));
        $this->get(route('login'))->assertSee('English School');
    }

    public function test_empty_app_nome_keeps_env_name(): void
    {
        $original = config('app.name');
        AppConfig::create(['key' => 'app_nome', 'value' => null]);

        (new CoreServiceProvider($this->app))->boot();

        $this->assertSame($original, config('app.name'));
    }

    public function test_login_shows_configured_logo_favicon_and_footer(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png')->store('app-configs', 'public');
        $favicon = UploadedFile::fake()->image('favicon.png')->store('app-configs', 'public');
        AppConfig::create(['key' => 'app_logo', 'media_path' => $logo]);
        AppConfig::create(['key' => 'app_favicon', 'media_path' => $favicon]);
        AppConfig::create(['key' => 'app_rodape', 'value' => 'Escola XYZ — CNPJ 00.000.000/0001-00']);

        $this->get(route('login'))
            ->assertSee('storage/'.$logo)
            ->assertSee('<link rel="icon" href="'.asset('storage/'.$favicon).'">', false)
            ->assertSee('Escola XYZ — CNPJ 00.000.000/0001-00')
            ->assertDontSee('Todos os direitos reservados');
    }

    public function test_get_does_not_cache_the_default(): void
    {
        $service = app(AppConfigService::class);

        $this->assertSame('a', $service->get('chave_inexistente', 'a'));
        $this->assertSame('b', $service->get('chave_inexistente', 'b'));
    }
}
