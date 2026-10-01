<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\AulaAoVivoManager;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use App\Notifications\AulaAoVivoLembrete;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class LembreteAulaAoVivoTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;

    private User $aluno;

    private Turma $turma;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.jitsi.timezone' => 'America/Sao_Paulo', 'services.jitsi.lembrete_minutos' => 15]);

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);

        $this->professor = User::factory()->create();
        $this->professor->assignRole('professor');

        $this->turma = Turma::factory()->create([
            'curso_id' => Curso::factory()->create()->id,
            'professor_id' => $this->professor->id,
        ]);

        $this->aluno = User::factory()->create();
        $this->aluno->assignRole('aluno');
        $this->turma->alunos()->attach($this->aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
    }

    /**
     * Aula digitada pro horário de Brasília "18:00". O app roda em UTC, então
     * 18:00 em Brasília = 21:00 UTC.
     */
    private function aulaAs18h(array $extra = []): AulaAoVivo
    {
        return AulaAoVivo::factory()->create($extra + [
            'turma_id' => $this->turma->id,
            'inicio_em' => '2026-10-01 18:00:00',
            'duracao_minutos' => 60,
        ]);
    }

    private function agoraUtc(string $hora): void
    {
        Carbon::setTestNow(Carbon::parse("2026-10-01 {$hora}:00", 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_calcula_os_minutos_no_fuso_das_aulas_e_nao_no_do_app(): void
    {
        $aula = $this->aulaAs18h();

        $this->agoraUtc('20:45'); // 17:45 em Brasília
        $this->assertSame(15, $aula->minutosParaComecar());

        $this->agoraUtc('21:00'); // 18:00 em Brasília
        $this->assertSame(0, $aula->minutosParaComecar());

        $this->agoraUtc('21:10');
        $this->assertSame(-10, $aula->minutosParaComecar());
    }

    public function test_nao_avisa_antes_da_janela(): void
    {
        Notification::fake();
        $this->aulaAs18h();

        $this->agoraUtc('20:30'); // 30 min antes

        $this->artisan('aulas:lembretes')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_avisa_15_minutos_antes_alunos_e_professor_so_uma_vez(): void
    {
        Notification::fake();
        $aula = $this->aulaAs18h();

        $this->agoraUtc('20:46'); // 14 min antes

        $this->artisan('aulas:lembretes')->assertSuccessful();
        $this->artisan('aulas:lembretes')->assertSuccessful();

        Notification::assertSentToTimes($this->aluno, AulaAoVivoLembrete::class, 1);
        Notification::assertSentToTimes($this->professor, AulaAoVivoLembrete::class, 1);
        $this->assertNotNull($aula->fresh()->lembrete_previo_em);
        $this->assertNull($aula->fresh()->lembrete_inicio_em);
    }

    public function test_avisa_na_hora_marcada_uma_vez_e_nao_repete_o_previo(): void
    {
        Notification::fake();
        $aula = $this->aulaAs18h(['lembrete_previo_em' => now()]);

        $this->agoraUtc('21:00');

        $this->artisan('aulas:lembretes')->assertSuccessful();
        $this->artisan('aulas:lembretes')->assertSuccessful();

        Notification::assertSentToTimes($this->aluno, AulaAoVivoLembrete::class, 1);
        $this->assertNotNull($aula->fresh()->lembrete_inicio_em);
    }

    public function test_aula_criada_em_cima_da_hora_recebe_os_dois_avisos_sem_duplicar(): void
    {
        Notification::fake();
        $this->aulaAs18h();

        $this->agoraUtc('20:55'); // 5 min antes: previo
        $this->artisan('aulas:lembretes');
        $this->agoraUtc('21:00'); // na hora: inicio
        $this->artisan('aulas:lembretes');

        Notification::assertSentToTimes($this->aluno, AulaAoVivoLembrete::class, 2);
    }

    public function test_nao_avisa_aula_ja_iniciada_ou_encerrada(): void
    {
        Notification::fake();
        $this->aulaAs18h(['status' => AulaAoVivo::AO_VIVO]);
        $this->aulaAs18h(['status' => AulaAoVivo::ENCERRADA, 'sala' => 'aula-outra']);

        $this->agoraUtc('20:50');
        $this->artisan('aulas:lembretes');

        Notification::assertNothingSent();
    }

    public function test_nao_avisa_aula_esquecida_que_ja_passou_da_duracao(): void
    {
        Notification::fake();
        $this->aulaAs18h(); // 18:00-19:00 em Brasília

        $this->agoraUtc('22:30'); // 19:30 em Brasília

        $this->artisan('aulas:lembretes');

        Notification::assertNothingSent();
    }

    public function test_so_avisa_quem_pertence_a_turma(): void
    {
        Notification::fake();
        $this->aulaAs18h();

        $fora = User::factory()->create();
        $fora->assignRole('aluno');
        $outroProfessor = User::factory()->create();
        $outroProfessor->assignRole('professor');

        $this->agoraUtc('20:50');
        $this->artisan('aulas:lembretes');

        Notification::assertNotSentTo($fora, AulaAoVivoLembrete::class);
        Notification::assertNotSentTo($outroProfessor, AulaAoVivoLembrete::class);
    }

    public function test_mensagem_muda_pro_professor_na_hora_de_iniciar(): void
    {
        $aula = $this->aulaAs18h();
        $notificacao = new AulaAoVivoLembrete($aula->load('turma'), AulaAoVivoLembrete::INICIO);

        $paraProfessor = $notificacao->toDatabase($this->professor)['message'];
        $paraAluno = $notificacao->toDatabase($this->aluno)['message'];

        $this->assertStringContainsString('clique em Iniciar', $paraProfessor);
        $this->assertStringContainsString('assim que o professor abrir a sala', $paraAluno);
    }

    public function test_remarcar_o_horario_zera_os_lembretes(): void
    {
        $aula = $this->aulaAs18h(['lembrete_previo_em' => now(), 'lembrete_inicio_em' => now()]);

        Livewire::actingAs($this->professor)
            ->test(AulaAoVivoManager::class, ['turma' => $this->turma])
            ->call('edit', $aula->id)
            ->set('inicioEm', '2026-10-02T19:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($aula->fresh()->lembrete_previo_em);
        $this->assertNull($aula->fresh()->lembrete_inicio_em);
    }

    public function test_editar_so_o_titulo_nao_zera_os_lembretes(): void
    {
        $aula = $this->aulaAs18h(['lembrete_previo_em' => now()]);

        Livewire::actingAs($this->professor)
            ->test(AulaAoVivoManager::class, ['turma' => $this->turma])
            ->call('edit', $aula->id)
            ->set('titulo', 'Novo título')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull($aula->fresh()->lembrete_previo_em);
    }

    public function test_o_comando_esta_agendado_a_cada_minuto(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('aulas:lembretes')->assertSuccessful();
    }
}
