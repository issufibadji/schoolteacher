<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\ModuloConteudoManager;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DialogoTest extends TestCase
{
    use RefreshDatabase;

    private const DIALOGO = <<<'TXT'
        Marie: Bonjour ! Comment tu t'appelles ? | Olá! Como você se chama?

        Paul: Je m'appelle Paul. Et toi ?
        (Marie sorri)
        10:30 — a aula começa
        Marie: Moi, c'est Marie. Enchantée ! | Eu sou a Marie. Prazer!
        TXT;

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
        $this->modulo = Modulo::factory()->create(['turma_id' => $this->turma->id, 'categoria' => 'nivel', 'nivel' => 'A1']);
    }

    private function gerenciador()
    {
        return Livewire::actingAs($this->professor)->test(ModuloConteudoManager::class, ['turma' => $this->turma]);
    }

    public function test_interpreta_falas_traducao_e_narracao(): void
    {
        $falas = Conteudo::interpretarDialogo(self::DIALOGO);

        $this->assertCount(5, $falas, 'linha vazia é ignorada');
        $this->assertSame(['personagem' => 'Marie', 'texto' => 'Bonjour ! Comment tu t\'appelles ?', 'traducao' => 'Olá! Como você se chama?'], $falas[0]);
        $this->assertSame(['personagem' => 'Paul', 'texto' => 'Je m\'appelle Paul. Et toi ?', 'traducao' => null], $falas[1]);
        $this->assertNull($falas[2]['personagem'], 'linha sem "Nome:" é narração');
        $this->assertNull($falas[3]['personagem'], '"10:30" não é nome de personagem');
        $this->assertSame(['Marie', 'Paul'], Conteudo::personagensDoDialogo($falas));
    }

    public function test_traco_com_espacos_tambem_separa_a_traducao(): void
    {
        $falas = Conteudo::interpretarDialogo(implode("\n", [
            'Marie: Sava ? - como você está ?',
            'John: Bien, et toi ? – Bem, e você?',
            'Marie: Merci ! — Obrigada!',
            "John: Est-ce que tu viens ? Peut-être.",
        ]));

        $this->assertSame(['Sava ?', 'como você está ?'], [$falas[0]['texto'], $falas[0]['traducao']]);
        $this->assertSame(['Bien, et toi ?', 'Bem, e você?'], [$falas[1]['texto'], $falas[1]['traducao']]);
        $this->assertSame(['Merci !', 'Obrigada!'], [$falas[2]['texto'], $falas[2]['traducao']]);
        // Hífen dentro da palavra (Est-ce, Peut-être) não é separador.
        $this->assertSame(['Est-ce que tu viens ? Peut-être.', null], [$falas[3]['texto'], $falas[3]['traducao']]);

        $this->assertSame(
            [['termo' => 'bonjour', 'traducao' => 'olá'], ['termo' => 'peut-être', 'traducao' => 'talvez']],
            Conteudo::interpretarVocabulario("bonjour - olá\npeut-être | talvez"),
        );
    }

    public function test_interpreta_vocabulario(): void
    {
        $this->assertSame(
            [['termo' => 'bonjour', 'traducao' => 'olá'], ['termo' => 'enchanté', 'traducao' => null]],
            Conteudo::interpretarVocabulario("bonjour | olá\n\n enchanté \n"),
        );
    }

    public function test_professor_cria_dialogo_com_audio_e_vocabulario_e_aluno_ve_em_baloes(): void
    {
        Storage::fake('public');

        $this->gerenciador()
            ->call('createConteudo', $this->modulo->id)
            ->set('titulo', 'Conversation')
            ->set('tipo', 'dialogo')
            ->set('corpo', self::DIALOGO)
            ->assertSee('Prévia — como o aluno vê')
            ->set('audio', UploadedFile::fake()->create('dialogo.mp3', 300, 'audio/mpeg'))
            ->set('vocabulario', "bonjour | olá\ns'appeler | chamar-se")
            ->call('saveConteudo')
            ->assertHasNoErrors();

        $conteudo = Conteudo::where('titulo', 'Conversation')->firstOrFail();
        $this->assertSame('dialogo', $conteudo->tipo);
        $this->assertNotNull($conteudo->audio_path);
        Storage::disk('public')->assertExists($conteudo->audio_path);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $this->turma->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $this->actingAs($aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('Diálogo')
            ->assertSeeInOrder(['Marie', 'Bonjour ! Comment tu t&#039;appelles ?', 'Paul', 'Je m&#039;appelle Paul.'], false)
            ->assertSee('Olá! Como você se chama?')
            ->assertSee('Mostrar tradução')
            ->assertSee('Praticar como:')
            ->assertSee('storage/'.$conteudo->audio_path)
            ->assertSee('Vocabulário da aula')
            ->assertSee('chamar-se');
    }

    public function test_dialogo_sem_falas_nao_salva(): void
    {
        $this->gerenciador()
            ->call('createConteudo', $this->modulo->id)
            ->set('titulo', 'Vazio')
            ->set('tipo', 'dialogo')
            ->set('corpo', "   \n  ")
            ->call('saveConteudo')
            ->assertHasErrors('corpo');

        $this->assertDatabaseMissing('conteudos', ['titulo' => 'Vazio']);
    }

    public function test_audio_invalido_e_rejeitado(): void
    {
        Storage::fake('public');

        $this->gerenciador()
            ->call('createConteudo', $this->modulo->id)
            ->set('titulo', 'Com exe')
            ->set('tipo', 'dialogo')
            ->set('corpo', 'Marie: Bonjour')
            ->set('audio', UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))
            ->call('saveConteudo')
            ->assertHasErrors('audio');
    }

    public function test_remover_audio_e_trocar_de_tipo_apagam_o_arquivo(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg')->store('conteudos-audio', 'public');
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $this->modulo->id, 'tipo' => 'dialogo', 'corpo' => 'Marie: Bonjour',
            'audio_path' => $path, 'vocabulario' => 'bonjour | olá',
        ]);

        $this->gerenciador()->call('editConteudo', $conteudo->id)->set('removerAudio', true)->call('saveConteudo')->assertHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($conteudo->fresh()->audio_path);
        $this->assertSame('bonjour | olá', $conteudo->fresh()->vocabulario);

        $path2 = UploadedFile::fake()->create('b.mp3', 10, 'audio/mpeg')->store('conteudos-audio', 'public');
        $conteudo->update(['audio_path' => $path2]);

        $this->gerenciador()->call('editConteudo', $conteudo->id)
            ->set('tipo', 'link')->set('urlExterna', 'https://example.com')
            ->call('saveConteudo')->assertHasNoErrors();

        Storage::disk('public')->assertMissing($path2);
        $this->assertNull($conteudo->fresh()->audio_path);
        $this->assertNull($conteudo->fresh()->vocabulario);
        $this->assertNull($conteudo->fresh()->corpo);
    }

    public function test_excluir_conteudo_apaga_o_audio(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg')->store('conteudos-audio', 'public');
        $conteudo = Conteudo::factory()->create(['modulo_id' => $this->modulo->id, 'tipo' => 'dialogo', 'corpo' => 'Marie: Oi', 'audio_path' => $path]);

        $this->gerenciador()->call('deleteConteudo', $conteudo->id);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('conteudos', ['id' => $conteudo->id]);
    }
}
