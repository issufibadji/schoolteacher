<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\AlunoManager;
use App\Livewire\Academico\CursoManager;
use App\Livewire\Academico\TurmaManager;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\MenuSideBarSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Professor só vê e mexe no que é dele (cursos que criou, turmas em que é o
 * responsável, alunos dessas turmas ou cadastrados por ele). Admin vê tudo.
 */
class EscopoProfessorTest extends TestCase
{
    use RefreshDatabase;

    private User $profA;

    private User $profB;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, AcademicoPermissionSeeder::class]);

        $this->profA = User::factory()->create(['name' => 'Prof A']);
        $this->profA->assignRole('professor');
        $this->profB = User::factory()->create(['name' => 'Prof B']);
        $this->profB->assignRole('professor');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_professor_ve_so_os_proprios_cursos_e_admin_ve_todos(): void
    {
        Curso::factory()->create(['nome' => 'Curso do A', 'professor_id' => $this->profA->id]);
        Curso::factory()->create(['nome' => 'Curso do B', 'professor_id' => $this->profB->id]);
        Curso::factory()->create(['nome' => 'Curso da Escola', 'professor_id' => null]);

        Livewire::actingAs($this->profA)->test(CursoManager::class)
            ->assertSee('Curso do A')->assertDontSee('Curso do B')->assertDontSee('Curso da Escola')
            ->assertSee('Atribuído a mim')
            ->call('create')
            ->assertSee('Atribuído a mim — Prof A');

        Livewire::actingAs($this->admin)->test(CursoManager::class)
            ->assertSee('Curso do A')->assertSee('Curso do B')->assertSee('Curso da Escola')->assertSee('Responsável');
    }

    public function test_professor_cria_curso_como_dono_e_nao_mexe_no_curso_de_outro(): void
    {
        Livewire::actingAs($this->profA)->test(CursoManager::class)
            ->call('create')->set('nome', 'Francês Básico')->set('professorId', $this->profB->id)
            ->call('save')->assertHasNoErrors();

        $this->assertSame($this->profA->id, Curso::where('nome', 'Francês Básico')->value('professor_id'), 'professor não escolhe outro dono');

        $cursoDoB = Curso::factory()->create(['professor_id' => $this->profB->id]);

        Livewire::actingAs($this->profA)->test(CursoManager::class)->call('edit', $cursoDoB->id)->assertForbidden();
        Livewire::actingAs($this->profA)->test(CursoManager::class)->call('delete', $cursoDoB->id)->assertForbidden();
        $this->assertDatabaseHas('cursos', ['id' => $cursoDoB->id]);
    }

    public function test_admin_atribui_o_professor_responsavel_do_curso(): void
    {
        Livewire::actingAs($this->admin)->test(CursoManager::class)
            ->call('create')->set('nome', 'Inglês Kids')->set('professorId', $this->profB->id)
            ->call('save')->assertHasNoErrors();

        $this->assertSame($this->profB->id, Curso::where('nome', 'Inglês Kids')->value('professor_id'));
    }

    public function test_professor_ve_so_as_proprias_turmas_e_admin_ve_todas(): void
    {
        Turma::factory()->create(['nome' => 'Turma do A', 'professor_id' => $this->profA->id, 'curso_id' => Curso::factory()->create(['professor_id' => $this->profA->id])->id]);
        Turma::factory()->create(['nome' => 'Turma do B', 'professor_id' => $this->profB->id, 'curso_id' => Curso::factory()->create(['professor_id' => $this->profB->id])->id]);

        Livewire::actingAs($this->profA)->test(TurmaManager::class)
            ->assertSee('Turma do A')->assertDontSee('Turma do B');

        Livewire::actingAs($this->admin)->test(TurmaManager::class)
            ->assertSee('Turma do A')->assertSee('Turma do B');
    }

    public function test_professor_cria_turma_so_no_proprio_curso_e_sempre_como_responsavel(): void
    {
        $cursoDoA = Curso::factory()->create(['professor_id' => $this->profA->id]);
        $cursoDoB = Curso::factory()->create(['professor_id' => $this->profB->id]);

        Livewire::actingAs($this->profA)->test(TurmaManager::class)
            ->call('create')->set('cursoId', $cursoDoB->id)->set('nome', 'Invasora')
            ->call('save')->assertHasErrors('cursoId');
        $this->assertDatabaseMissing('turmas', ['nome' => 'Invasora']);

        Livewire::actingAs($this->profA)->test(TurmaManager::class)
            ->call('create')->set('cursoId', $cursoDoA->id)->set('professorId', $this->profB->id)->set('nome', 'Turma Sábado')
            ->call('save')->assertHasNoErrors();
        $this->assertSame($this->profA->id, Turma::where('nome', 'Turma Sábado')->value('professor_id'));
    }

    public function test_professor_nao_edita_nem_exclui_turma_de_outro(): void
    {
        $turmaDoB = Turma::factory()->create(['professor_id' => $this->profB->id, 'curso_id' => Curso::factory()->create(['professor_id' => $this->profB->id])->id]);

        Livewire::actingAs($this->profA)->test(TurmaManager::class)->call('edit', $turmaDoB->id)->assertForbidden();
        Livewire::actingAs($this->profA)->test(TurmaManager::class)->call('delete', $turmaDoB->id)->assertForbidden();
        $this->assertDatabaseHas('turmas', ['id' => $turmaDoB->id]);

        foreach (['academico.turmas.conteudo', 'academico.turmas.matricula', 'academico.turmas.ao-vivo'] as $rota) {
            $this->actingAs($this->profA)->get(route($rota, $turmaDoB))->assertForbidden();
        }
    }

    public function test_meus_alunos_so_mostra_alunos_do_professor(): void
    {
        $turmaDoA = Turma::factory()->create(['professor_id' => $this->profA->id, 'curso_id' => Curso::factory()->create(['professor_id' => $this->profA->id])->id]);
        $turmaDoB = Turma::factory()->create(['professor_id' => $this->profB->id, 'curso_id' => Curso::factory()->create(['professor_id' => $this->profB->id])->id]);

        $matriculadoNoA = User::factory()->create(['name' => 'Aluno Matriculado A']);
        $criadoPeloA = User::factory()->create(['name' => 'Aluno Criado A', 'criado_por' => $this->profA->id]);
        $doB = User::factory()->create(['name' => 'Aluno do B']);
        foreach ([$matriculadoNoA, $criadoPeloA, $doB] as $aluno) {
            $aluno->assignRole('aluno');
        }
        $turmaDoA->alunos()->attach($matriculadoNoA->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
        $turmaDoB->alunos()->attach($doB->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        Livewire::actingAs($this->profA)->test(AlunoManager::class)
            ->assertSee('Aluno Matriculado A')
            ->assertSee('Aluno Criado A')
            ->assertSee('Sem matrícula')
            ->assertDontSee('Aluno do B');
    }

    public function test_menu_do_professor_tem_cursos_turmas_e_usuarios_mas_nao_telas_de_admin(): void
    {
        $this->seed(MenuSideBarSeeder::class);

        $this->actingAs($this->profA)->get(route('dashboard'))
            ->assertSee(route('academico.cursos.index'), false)
            ->assertSee(route('academico.turmas.index'), false)
            ->assertSee(route('academico.meus-alunos.index'), false)
            ->assertSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.config.index'), false)
            ->assertDontSee(route('academico.minhas-turmas.index'), false);

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertSee(route('academico.cursos.index'), false)
            ->assertSee(route('admin.users.index'), false);
    }

    public function test_migracao_transforma_manager_em_professor_e_remove_papeis(): void
    {
        $agora = now();
        $managerRole = DB::table('roles')->insertGetId(['name' => 'manager', 'guard_name' => 'web', 'created_at' => $agora, 'updated_at' => $agora]);
        $operatorRole = DB::table('roles')->insertGetId(['name' => 'operator', 'guard_name' => 'web', 'created_at' => $agora, 'updated_at' => $agora]);

        $exManager = User::factory()->create();
        $exOperator = User::factory()->create();
        DB::table('model_has_roles')->insert([
            ['role_id' => $managerRole, 'model_type' => User::class, 'model_id' => $exManager->id],
            ['role_id' => $operatorRole, 'model_type' => User::class, 'model_id' => $exOperator->id],
        ]);

        // Curso com turmas de um só professor ganha dono; com dois professores, fica com o admin.
        $cursoUnico = Curso::factory()->create();
        Turma::factory()->count(2)->create(['curso_id' => $cursoUnico->id, 'professor_id' => $this->profA->id]);
        $cursoDividido = Curso::factory()->create();
        Turma::factory()->create(['curso_id' => $cursoDividido->id, 'professor_id' => $this->profA->id]);
        Turma::factory()->create(['curso_id' => $cursoDividido->id, 'professor_id' => $this->profB->id]);

        $migracao = require database_path('migrations/2026_10_22_100100_consolidar_papeis_em_professor.php');
        $migracao->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertDatabaseMissing('roles', ['name' => 'manager']);
        $this->assertDatabaseMissing('roles', ['name' => 'operator']);
        $this->assertTrue($exManager->fresh()->hasRole('professor'));
        $this->assertTrue($exManager->fresh()->can('manage-cursos'));
        $this->assertSame([], $exOperator->fresh()->getRoleNames()->all());
        $this->assertSame($this->profA->id, $cursoUnico->fresh()->professor_id);
        $this->assertNull($cursoDividido->fresh()->professor_id);
    }
}
