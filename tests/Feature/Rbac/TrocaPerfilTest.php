<?php

namespace Tests\Feature\Rbac;

use App\Livewire\TrocaPerfil;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrocaPerfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    private function gestorProfessor(): User
    {
        $user = User::factory()->create(['name' => 'Diretora Ana']);
        $user->assignRole(['manager', 'professor']);

        return $user;
    }

    public function test_switcher_only_appears_for_users_with_both_profiles(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        Livewire::actingAs($professor)->test(TrocaPerfil::class)->assertDontSee('Diretor');
        Livewire::actingAs($manager)->test(TrocaPerfil::class)->assertDontSee('Professor');

        Livewire::actingAs($this->gestorProfessor())->test(TrocaPerfil::class)
            ->assertSee('Diretor')
            ->assertSee('Professor');
    }

    public function test_user_with_both_profiles_starts_as_manager(): void
    {
        $user = $this->gestorProfessor();

        $this->actingAs($user);

        $this->assertSame('manager', $user->perfilAtivo());
        $this->get(route('academico.cursos.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('academico.minhas-turmas.index'))->assertForbidden();
    }

    public function test_switching_to_professor_swaps_permissions(): void
    {
        $user = $this->gestorProfessor();

        Livewire::actingAs($user)->test(TrocaPerfil::class)
            ->call('trocar', 'professor')
            ->assertRedirect(route('dashboard'));

        $this->assertSame('professor', $user->perfilAtivo());
        $this->get(route('academico.minhas-turmas.index'))->assertOk();
        $this->get(route('academico.cursos.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();

        // E volta pra gestor.
        Livewire::actingAs($user)->test(TrocaPerfil::class)->call('trocar', 'manager');

        $this->get(route('academico.cursos.index'))->assertOk();
        $this->get(route('academico.minhas-turmas.index'))->assertForbidden();
    }

    public function test_dashboard_follows_the_active_profile(): void
    {
        $user = $this->gestorProfessor();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertDontSee('Área do Professor');

        $user->trocarPerfil('professor');

        $this->get(route('dashboard'))->assertSee('Área do Professor');
    }

    public function test_cannot_switch_to_a_profile_the_user_does_not_have(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        Livewire::actingAs($professor)->test(TrocaPerfil::class)
            ->call('trocar', 'manager')
            ->assertForbidden();

        Livewire::actingAs($this->gestorProfessor())->test(TrocaPerfil::class)
            ->call('trocar', 'admin')
            ->assertForbidden();
    }

    public function test_active_profile_of_logged_user_does_not_affect_other_users(): void
    {
        $logado = $this->gestorProfessor();
        $outro = $this->gestorProfessor();

        $this->actingAs($logado);
        $logado->trocarPerfil('professor');

        $this->assertFalse($logado->can('manage-cursos'));
        $this->assertTrue($outro->can('manage-cursos'));
        $this->assertTrue($outro->can('manage-own-turmas'));
    }

    public function test_pure_manager_no_longer_manages_own_turmas(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->assertFalse($manager->can('manage-own-turmas'));
        $this->assertTrue($manager->can('manage-turmas'));
    }

    public function test_seeder_gives_professor_role_to_managers_who_own_turmas(): void
    {
        $managerComTurma = User::factory()->create();
        $managerComTurma->assignRole('manager');
        Turma::factory()->create(['curso_id' => Curso::factory()->create()->id, 'professor_id' => $managerComTurma->id]);

        $managerSemTurma = User::factory()->create();
        $managerSemTurma->assignRole('manager');

        $this->seed(AcademicoPermissionSeeder::class);

        $this->assertTrue($managerComTurma->fresh()->hasRole('professor'));
        $this->assertFalse($managerSemTurma->fresh()->hasRole('professor'));
    }
}
