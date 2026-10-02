<?php

namespace Tests\Feature\Auth;

use App\Livewire\Admin\AppConfigManager;
use App\Models\AppConfig;
use App\Models\User;
use Database\Seeders\AppConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LoginConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_default_illustration_and_texts_without_config(): void
    {
        $this->seed(AppConfigSeeder::class);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Professor estudando no notebook')
            ->assertSee('Bem-vindo')
            ->assertSee('Gestão inteligente, em um só lugar.');
    }

    public function test_login_shows_configured_image_and_texts(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('aluno.png')->store('app-configs', 'public');
        AppConfig::create(['key' => 'login_imagem', 'media_path' => $path]);
        AppConfig::create(['key' => 'login_subtitulo', 'value' => 'Olá, aluno']);
        AppConfig::create(['key' => 'login_titulo', 'value' => 'Aprenda inglês do seu jeito.']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('storage/'.$path)
            ->assertDontSee('Professor estudando no notebook')
            ->assertSee('Olá, aluno')
            ->assertSee('Aprenda inglês do seu jeito.');
    }

    public function test_admin_upload_of_login_image_reflects_on_login_page(): void
    {
        Storage::fake('public');
        $this->seed([RolePermissionSeeder::class, AppConfigSeeder::class]);

        // Aquece o cache sem imagem: a troca pelo admin precisa invalidá-lo.
        $this->get(route('login'))->assertSee('Professor estudando no notebook');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AppConfigManager::class)
            ->call('edit', AppConfig::where('key', 'login_imagem')->value('id'))
            ->set('media', UploadedFile::fake()->image('professor.png'))
            ->call('save');

        auth()->logout();

        $path = AppConfig::where('key', 'login_imagem')->value('media_path');

        $this->get(route('login'))
            ->assertSee('storage/'.$path)
            ->assertDontSee('Professor estudando no notebook');
    }
}
