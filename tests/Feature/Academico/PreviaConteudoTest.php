<?php

namespace Tests\Feature\Academico;

use App\Models\AlunoProgresso;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\ExercicioOpcao;
use App\Models\ExercicioPergunta;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviaConteudoTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;

    private Turma $turma;

    private Modulo $modulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, AcademicoPermissionSeeder::class]);

        $this->professor = User::factory()->create();
        $this->professor->assignRole('professor');

        $this->turma = Turma::factory()->create(['curso_id' => Curso::factory()->create()->id, 'professor_id' => $this->professor->id]);
        $this->modulo = Modulo::factory()->create(['turma_id' => $this->turma->id, 'categoria' => 'nivel', 'nivel' => 'A1', 'nome' => 'Módulo 01']);
    }

    private function conteudo(array $attrs = []): Conteudo
    {
        return Conteudo::factory()->create($attrs + [
            'modulo_id' => $this->modulo->id,
            'titulo' => 'Aula 02 - Les Sons',
            'tipo' => 'texto',
            'corpo' => 'Texto da aula para o aluno.',
            'dias_liberacao' => 0,
            'bloqueado' => false,
        ]);
    }

    private function urlPrevia(Conteudo $conteudo): string
    {
        return route('academico.turmas.conteudo.previa', [$this->turma, $conteudo]);
    }

    public function test_content_list_has_a_preview_link(): void
    {
        $conteudo = $this->conteudo();

        $this->actingAs($this->professor)
            ->get(route('academico.turmas.conteudo', $this->turma))
            ->assertOk()
            ->assertSee($this->urlPrevia($conteudo), false)
            ->assertSee('Visualizar');
    }

    public function test_professor_sees_the_lesson_as_the_student_without_progress_actions(): void
    {
        $conteudo = $this->conteudo();

        $this->actingAs($this->professor)
            ->get($this->urlPrevia($conteudo))
            ->assertOk()
            ->assertSee('Pré-visualização')
            ->assertSee('Aula 02 - Les Sons')
            ->assertSee('Texto da aula para o aluno.')
            ->assertSee('Liberada para o aluno logo após a matrícula.')
            ->assertDontSee('Marcar como concluída');
    }

    public function test_preview_shows_locked_and_scheduled_content_with_the_release_rule(): void
    {
        $bloqueado = $this->conteudo(['titulo' => 'Bloqueada', 'bloqueado' => true]);
        $agendado = $this->conteudo(['titulo' => 'Agendada', 'dias_liberacao' => 7]);

        $this->actingAs($this->professor)->get($this->urlPrevia($bloqueado))
            ->assertOk()->assertSee('Bloqueada manualmente')->assertSee('Texto da aula para o aluno.');

        $this->actingAs($this->professor)->get($this->urlPrevia($agendado))
            ->assertOk()->assertSee('7 dia(s) após a matrícula')->assertSee('Texto da aula para o aluno.');
    }

    public function test_quiz_preview_shows_answer_key_and_saves_nothing(): void
    {
        $quiz = $this->conteudo(['tipo' => 'exercicio', 'exercicio_subtipo' => 'quiz', 'corpo' => null]);
        $pergunta = ExercicioPergunta::factory()->create(['conteudo_id' => $quiz->id, 'enunciado' => 'Como se diz olá?']);
        ExercicioOpcao::factory()->correta()->create(['pergunta_id' => $pergunta->id, 'texto' => 'Bonjour']);
        ExercicioOpcao::factory()->incorreta()->create(['pergunta_id' => $pergunta->id, 'texto' => 'Merci']);

        $this->actingAs($this->professor)->get($this->urlPrevia($quiz))
            ->assertOk()
            ->assertSee('Como se diz olá?')
            ->assertSeeInOrder(['Bonjour', 'correta', 'Merci'])
            ->assertDontSee('wire:click="enviarQuiz"', false);

        $this->assertSame(0, AlunoProgresso::count());
    }

    public function test_other_professor_and_student_cannot_preview(): void
    {
        $conteudo = $this->conteudo();

        $outroProfessor = User::factory()->create();
        $outroProfessor->assignRole('professor');
        $this->actingAs($outroProfessor)->get($this->urlPrevia($conteudo))->assertForbidden();

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $this->turma->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
        $this->actingAs($aluno)->get($this->urlPrevia($conteudo))->assertForbidden();
    }

    public function test_content_from_another_turma_returns_404(): void
    {
        $outraTurma = Turma::factory()->create(['curso_id' => $this->turma->curso_id, 'professor_id' => $this->professor->id]);
        $conteudo = $this->conteudo();

        $this->actingAs($this->professor)
            ->get(route('academico.turmas.conteudo.previa', [$outraTurma, $conteudo]))
            ->assertNotFound();
    }
}
