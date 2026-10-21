<?php

namespace App\Livewire\Academico;

use App\Models\Conteudo;
use App\Models\Turma;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pré-visualização de um conteúdo pelo professor: a mesma tela que o aluno vê
 * (view aluno/aula), mas sempre liberada, sem registrar progresso nem resposta
 * de quiz, e mostrando o gabarito e a regra de liberação.
 */
#[Layout('components.layouts.master')]
class PreviaConteudo extends Component
{
    public Turma $turma;

    public Conteudo $conteudo;

    public function mount(Turma $turma, Conteudo $conteudo): void
    {
        abort_unless($conteudo->modulo->turma_id === $turma->id, 404);

        Gate::authorize('manageConteudo', $turma);

        $this->turma = $turma;
        $this->conteudo = $conteudo;
    }

    public function render()
    {
        $conteudo = $this->conteudo;

        return view('livewire.academico.aluno.aula', [
            'previa' => true,
            'modulo' => $conteudo->modulo,
            'disponivel' => true,
            'diasRestantes' => 0,
            'concluido' => false,
            'ehQuiz' => $conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo === 'quiz',
            'score' => null,
            'vizinhos' => $this->turma->vizinhosDoConteudo($conteudo),
        ]);
    }
}
