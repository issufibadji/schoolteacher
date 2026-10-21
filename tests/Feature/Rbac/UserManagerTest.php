<?php

namespace Tests\Feature\Rbac;

use App\Livewire\Admin\UserManager;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->set('name', 'New User')
            ->set('email', 'new-user@example.com')
            ->set('password', 'password123')
            ->set('roles', ['professor'])
            ->call('save');

        $user = User::where('email', 'new-user@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('professor'));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_toggling_active_blocks_the_user_from_logging_in(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create(['active' => true]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('toggleActive', $target->id);

        $this->assertFalse($target->fresh()->active);
    }

    public function test_toggling_requires_2fa(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create(['requires_2fa' => false]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('toggleRequires2fa', $target->id);

        $this->assertTrue($target->fresh()->requires_2fa);
    }

    public function test_admin_cannot_assign_a_role_that_does_not_exist(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->set('name', 'Papel Removido')
            ->set('email', 'papel-removido@example.com')
            ->set('password', 'password123')
            ->set('roles', ['manager'])
            ->call('save')
            ->assertHasErrors('roles.0');

        $this->assertNull(User::where('email', 'papel-removido@example.com')->first());
    }

    private function professorComAlunos(): array
    {
        $professor = User::factory()->create(['name' => 'Prof Ana']);
        $professor->assignRole('professor');

        $criadoPorEle = User::factory()->create(['name' => 'Aluno Criado Pela Ana', 'criado_por' => $professor->id]);
        $criadoPorEle->assignRole('aluno');

        // Aluno criado pelo admin e matriculado numa turma da professora: ela vê, mas não edita.
        $matriculado = User::factory()->create(['name' => 'Aluno Matriculado Na Ana']);
        $matriculado->assignRole('aluno');
        $turma = Turma::factory()->create(['professor_id' => $professor->id, 'curso_id' => Curso::factory()->create(['professor_id' => $professor->id])->id]);
        $turma->alunos()->attach($matriculado->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        return [$professor, $criadoPorEle, $matriculado];
    }

    public function test_professor_sees_only_his_students_in_the_users_screen(): void
    {
        [$professor] = $this->professorComAlunos();

        $outroProfessor = User::factory()->create(['name' => 'Prof Outro']);
        $outroProfessor->assignRole('professor');
        $alunoDeOutro = User::factory()->create(['name' => 'Aluno De Outro', 'criado_por' => $outroProfessor->id]);
        $alunoDeOutro->assignRole('aluno');
        User::factory()->create(['name' => 'Zé Admin'])->assignRole('admin');

        $this->actingAs($professor)->get(route('admin.users.index'))->assertOk();

        Livewire::actingAs($professor)->test(UserManager::class)
            ->assertSee('Aluno Criado Pela Ana')
            ->assertSee('Aluno Matriculado Na Ana')
            ->assertDontSee('Aluno De Outro')
            ->assertDontSee('Prof Outro')
            ->assertDontSee('Zé Admin')
            ->assertSee('+ Aluno');
    }

    public function test_professor_creates_users_only_as_aluno(): void
    {
        [$professor] = $this->professorComAlunos();

        Livewire::actingAs($professor)
            ->test(UserManager::class)
            ->call('create')
            ->assertSet('roles', ['aluno'])
            ->set('name', 'Tentou Ser Admin')
            ->set('email', 'tentou-admin@example.com')
            ->set('password', 'password123')
            ->set('roles', ['admin', 'professor'])
            ->call('save')
            ->assertHasNoErrors();

        $novo = User::where('email', 'tentou-admin@example.com')->firstOrFail();

        $this->assertSame(['aluno'], $novo->getRoleNames()->all(), 'o papel é forçado em aluno');
        $this->assertSame($professor->id, $novo->criado_por);
    }

    public function test_professor_edits_and_deletes_only_students_he_created(): void
    {
        [$professor, $criadoPorEle, $matriculado] = $this->professorComAlunos();

        Livewire::actingAs($professor)->test(UserManager::class)
            ->call('edit', $criadoPorEle->id)
            ->set('name', 'Nome Corrigido')
            ->set('roles', ['admin'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nome Corrigido', $criadoPorEle->fresh()->name);
        $this->assertSame(['aluno'], $criadoPorEle->fresh()->getRoleNames()->all(), 'não promove ao editar');

        Livewire::actingAs($professor)->test(UserManager::class)->call('edit', $matriculado->id)->assertForbidden();
        Livewire::actingAs($professor)->test(UserManager::class)->call('toggleActive', $matriculado->id)->assertForbidden();
        Livewire::actingAs($professor)->test(UserManager::class)->call('delete', $matriculado->id)->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $matriculado->id]);

        Livewire::actingAs($professor)->test(UserManager::class)->call('delete', $criadoPorEle->id);
        $this->assertDatabaseMissing('users', ['id' => $criadoPorEle->id]);
    }

    public function test_professor_cannot_touch_other_professors_or_admins(): void
    {
        [$professor] = $this->professorComAlunos();

        $outroProfessor = User::factory()->create(['criado_por' => $professor->id]);
        $outroProfessor->assignRole('professor');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        foreach ([$outroProfessor, $admin] as $alvo) {
            Livewire::actingAs($professor)->test(UserManager::class)->call('edit', $alvo->id)->assertForbidden();
            Livewire::actingAs($professor)->test(UserManager::class)->call('delete', $alvo->id)->assertForbidden();
        }
    }

    public function test_aluno_cannot_open_the_users_screen(): void
    {
        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');

        $this->actingAs($aluno)->get(route('admin.users.index'))->assertForbidden();
        Livewire::actingAs($aluno)->test(UserManager::class)->call('create')->assertForbidden();
    }
}
