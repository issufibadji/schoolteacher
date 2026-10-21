<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\MatriculaManager;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MatriculaManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    private function professor(): User
    {
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        return $professor;
    }

    private function turmaDe(User $professor): Turma
    {
        return Turma::factory()->create([
            'curso_id' => Curso::factory()->create(['professor_id' => $professor->id])->id,
            'professor_id' => $professor->id,
        ]);
    }

    private function aluno(array $attrs = []): User
    {
        $aluno = User::factory()->create($attrs);
        $aluno->assignRole('aluno');

        return $aluno;
    }

    public function test_admin_can_enroll_any_student_in_any_turma(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $turma = $this->turmaDe($this->professor());
        $aluno = $this->aluno();

        Livewire::actingAs($admin)
            ->test(MatriculaManager::class, ['turma' => $turma])
            ->assertSee($aluno->email)
            ->set('alunoId', $aluno->id)
            ->call('matricular');

        $this->assertDatabaseHas('turma_aluno', ['turma_id' => $turma->id, 'aluno_id' => $aluno->id]);
    }

    public function test_professor_enrolls_and_unenrolls_own_students_in_own_turma(): void
    {
        $professor = $this->professor();
        $turma = $this->turmaDe($professor);
        $aluno = $this->aluno(['criado_por' => $professor->id]);

        $this->actingAs($professor)->get(route('academico.turmas.matricula', $turma))->assertOk();

        Livewire::actingAs($professor)
            ->test(MatriculaManager::class, ['turma' => $turma])
            ->set('alunoId', $aluno->id)
            ->call('matricular')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('turma_aluno', ['turma_id' => $turma->id, 'aluno_id' => $aluno->id]);

        Livewire::actingAs($professor)
            ->test(MatriculaManager::class, ['turma' => $turma])
            ->call('desmatricular', $aluno->id);

        $this->assertDatabaseMissing('turma_aluno', ['turma_id' => $turma->id, 'aluno_id' => $aluno->id]);
    }

    public function test_professor_does_not_see_nor_enroll_students_of_another_professor(): void
    {
        $professorA = $this->professor();
        $professorB = $this->professor();
        $turmaA = $this->turmaDe($professorA);

        $alunoDeB = $this->aluno(['email' => 'aluno-do-b@example.com']);
        $this->turmaDe($professorB)->alunos()->attach($alunoDeB->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
        $alunoSemDono = $this->aluno(['email' => 'aluno-sem-dono@example.com']);

        Livewire::actingAs($professorA)
            ->test(MatriculaManager::class, ['turma' => $turmaA])
            ->assertDontSee('aluno-do-b@example.com')
            ->assertDontSee('aluno-sem-dono@example.com')
            ->set('alunoId', $alunoDeB->id)
            ->call('matricular')
            ->assertForbidden();

        $this->assertDatabaseMissing('turma_aluno', ['turma_id' => $turmaA->id, 'aluno_id' => $alunoDeB->id]);
        $this->assertDatabaseMissing('turma_aluno', ['turma_id' => $turmaA->id, 'aluno_id' => $alunoSemDono->id]);
    }

    public function test_professor_cannot_open_enrollment_of_another_professors_turma(): void
    {
        $turmaDeOutro = $this->turmaDe($this->professor());

        $this->actingAs($this->professor())
            ->get(route('academico.turmas.matricula', $turmaDeOutro))
            ->assertForbidden();
    }

    public function test_professor_creates_a_new_student_already_enrolled(): void
    {
        $professor = $this->professor();
        $turma = $this->turmaDe($professor);

        Livewire::actingAs($professor)
            ->test(MatriculaManager::class, ['turma' => $turma])
            ->call('abrirNovoAluno')
            ->set('novoNome', 'Julia Martins')
            ->set('novoEmail', 'julia@example.com')
            ->set('novaSenha', 'senha-forte-1')
            ->call('criarAluno')
            ->assertHasNoErrors();

        $aluno = User::where('email', 'julia@example.com')->firstOrFail();

        $this->assertTrue($aluno->hasRole('aluno'));
        $this->assertSame($professor->id, $aluno->criado_por);
        $this->assertNotNull($aluno->email_verified_at);
        $this->assertDatabaseHas('turma_aluno', ['turma_id' => $turma->id, 'aluno_id' => $aluno->id]);

        // Aparece em "Meus Alunos" do professor.
        $this->actingAs($professor)->get(route('academico.meus-alunos.index'))->assertSee('Julia Martins');
    }

    public function test_new_student_with_existing_email_is_rejected(): void
    {
        $professor = $this->professor();
        $this->aluno(['email' => 'ja-existe@example.com']);

        Livewire::actingAs($professor)
            ->test(MatriculaManager::class, ['turma' => $this->turmaDe($professor)])
            ->set('novoNome', 'Outro')
            ->set('novoEmail', 'ja-existe@example.com')
            ->set('novaSenha', 'senha-forte-1')
            ->call('criarAluno')
            ->assertHasErrors('novoEmail');
    }

    public function test_enrolling_the_same_student_twice_does_not_duplicate_the_row(): void
    {
        $professor = $this->professor();
        $turma = $this->turmaDe($professor);
        $aluno = $this->aluno(['criado_por' => $professor->id]);

        $component = Livewire::actingAs($professor)->test(MatriculaManager::class, ['turma' => $turma]);
        $component->set('alunoId', $aluno->id)->call('matricular');
        $component->set('alunoId', $aluno->id)->call('matricular');

        $this->assertSame(1, $turma->alunos()->where('users.id', $aluno->id)->count());
    }
}
