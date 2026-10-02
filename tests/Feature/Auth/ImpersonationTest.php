<?php

namespace Tests\Feature\Auth;

use App\Livewire\Admin\UserManager;
use App\Livewire\Settings\TwoFactorSettings;
use App\Models\User;
use App\Services\ImpersonationService;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, AcademicoPermissionSeeder::class]);
    }

    private function usuario(string $role, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_access_as_another_user_and_go_back(): void
    {
        $admin = $this->usuario('admin', ['name' => 'Admin Chefe']);
        $professor = $this->usuario('professor', ['name' => 'Prof Carla']);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->assertSee('Acessar como')
            ->call('acessarComo', $professor->id)
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(auth()->user()->is($professor));
        $this->assertSame($admin->id, session(ImpersonationService::SESSION_KEY));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Você (Admin Chefe) está acessando como')
            ->assertSee('Prof Carla');

        // Como professor, não entra em tela de admin.
        $this->get(route('admin.users.index'))->assertForbidden();

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users.index'));

        $this->assertTrue(auth()->user()->is($admin));
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
        $this->get(route('admin.users.index'))->assertOk()->assertDontSee('está acessando como');
    }

    public function test_access_bypasses_target_two_factor_challenge(): void
    {
        $admin = $this->usuario('admin');
        $aluno = $this->usuario('aluno', ['two_factor_confirmed_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('acessarComo', $aluno->id);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_only_admin_can_access_as_another_user(): void
    {
        $manager = $this->usuario('manager');
        $professor = $this->usuario('professor');

        Livewire::actingAs($manager)->test(UserManager::class)
            ->assertDontSee('Acessar como')
            ->call('acessarComo', $professor->id)
            ->assertForbidden();

        $this->assertTrue(auth()->user()->is($manager));
    }

    public function test_admin_cannot_access_as_another_admin_or_himself(): void
    {
        $admin = $this->usuario('admin');
        $outroAdmin = $this->usuario('admin');

        Livewire::actingAs($admin)->test(UserManager::class)->call('acessarComo', $outroAdmin->id)->assertForbidden();
        Livewire::actingAs($admin)->test(UserManager::class)->call('acessarComo', $admin->id)->assertForbidden();
    }

    public function test_stop_without_impersonation_is_forbidden(): void
    {
        $this->actingAs($this->usuario('professor'))->post(route('impersonate.stop'))->assertForbidden();
    }

    public function test_two_factor_settings_are_blocked_while_impersonating(): void
    {
        $admin = $this->usuario('admin');
        $professor = $this->usuario('professor');

        Livewire::actingAs($admin)->test(UserManager::class)->call('acessarComo', $professor->id);

        Livewire::test(TwoFactorSettings::class)->assertForbidden();
    }

    public function test_start_and_stop_are_audited_without_a_fake_login(): void
    {
        $admin = $this->usuario('admin');
        $professor = $this->usuario('professor');

        Livewire::actingAs($admin)->test(UserManager::class)->call('acessarComo', $professor->id);
        $this->post(route('impersonate.stop'));

        $this->assertDatabaseHas('audits', ['event' => 'impersonate-start', 'user_id' => $admin->id, 'auditable_id' => $professor->id]);
        $this->assertDatabaseHas('audits', ['event' => 'impersonate-stop', 'user_id' => $admin->id, 'auditable_id' => $professor->id]);
        $this->assertSame(0, Audit::where('event', 'login')->where('user_id', $professor->id)->count());
    }
}
