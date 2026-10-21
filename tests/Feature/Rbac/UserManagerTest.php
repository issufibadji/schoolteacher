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

        // Aluno criado pelo admin e matriculado numa turma da professora: também é dela.
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

    public function test_professor_edits_and_deletes_his_students_but_not_shared_ones(): void
    {
        [$professor, $criadoPorEle, $matriculado] = $this->professorComAlunos();

        // Edita tanto o que cadastrou quanto o matriculado na turma dele; não promove ao editar.
        foreach ([$criadoPorEle, $matriculado] as $aluno) {
            Livewire::actingAs($professor)->test(UserManager::class)
                ->call('edit', $aluno->id)
                ->set('name', "Editado {$aluno->id}")
                ->set('roles', ['admin'])
                ->call('save')
                ->assertHasNoErrors();

            $this->assertSame("Editado {$aluno->id}", $aluno->fresh()->name);
            $this->assertSame(['aluno'], $aluno->fresh()->getRoleNames()->all());
        }

        Livewire::actingAs($professor)->test(UserManager::class)->call('toggleActive', $matriculado->id)->assertHasNoErrors();
        // Exigir 2FA continua só do admin.
        Livewire::actingAs($professor)->test(UserManager::class)->assertDontSee('Exigir 2FA')->call('toggleRequires2fa', $matriculado->id)->assertForbidden();
        $this->assertFalse($matriculado->fresh()->active);

        // Aluno que também está na turma de outro professor: edita, mas não exclui.
        $outro = User::factory()->create();
        $outro->assignRole('professor');
        $turmaDoOutro = Turma::factory()->create(['professor_id' => $outro->id, 'curso_id' => Curso::factory()->create(['professor_id' => $outro->id])->id]);
        $compartilhado = User::factory()->create(['name' => 'Aluno Compartilhado', 'criado_por' => $professor->id]);
        $compartilhado->assignRole('aluno');
        $turmaDoOutro->alunos()->attach($compartilhado->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        Livewire::actingAs($professor)->test(UserManager::class)
            ->assertSee('Compartilhado')
            ->call('delete', $compartilhado->id)
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $compartilhado->id]);

        // Os dele (não compartilhados) ele exclui.
        Livewire::actingAs($professor)->test(UserManager::class)->call('delete', $matriculado->id);
        Livewire::actingAs($professor)->test(UserManager::class)->call('delete', $criadoPorEle->id);
        $this->assertDatabaseMissing('users', ['id' => $matriculado->id]);
        $this->assertDatabaseMissing('users', ['id' => $criadoPorEle->id]);
    }

    public function test_professor_cannot_touch_students_of_another_professor(): void
    {
        [$professor] = $this->professorComAlunos();

        $outro = User::factory()->create();
        $outro->assignRole('professor');
        $alunoDoOutro = User::factory()->create(['criado_por' => $outro->id]);
        $alunoDoOutro->assignRole('aluno');

        Livewire::actingAs($professor)->test(UserManager::class)->call('edit', $alunoDoOutro->id)->assertForbidden();
        Livewire::actingAs($professor)->test(UserManager::class)->call('delete', $alunoDoOutro->id)->assertForbidden();
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
