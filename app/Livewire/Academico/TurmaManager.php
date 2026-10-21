<?php

namespace App\Livewire\Academico;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Professor: vê e gerencia só as turmas dele, criadas nos cursos dele, e é
 * sempre o responsável. Admin: vê todas e escolhe curso e professor.
 */
#[Layout('components.layouts.master')]
class TurmaManager extends Component
{
    public ?int $turmaId = null;

    #[Validate('required|exists:cursos,id')]
    public ?int $cursoId = null;

    /** Só o admin escolhe; para o professor é sempre ele mesmo. */
    public ?int $professorId = null;

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('nullable|date')]
    public string $dataInicio = '';

    #[Validate('nullable|date')]
    public string $dataFim = '';

    public bool $ativo = true;

    public function create(): void
    {
        $this->reset(['turmaId', 'cursoId', 'professorId', 'nome', 'dataInicio', 'dataFim']);
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'turma-form');
    }

    public function edit(int $id): void
    {
        $turma = Turma::findOrFail($id);

        Gate::authorize('update', $turma);

        $this->turmaId = $turma->id;
        $this->cursoId = $turma->curso_id;
        $this->professorId = $turma->professor_id;
        $this->nome = $turma->nome;
        $this->dataInicio = $turma->data_inicio?->format('Y-m-d') ?? '';
        $this->dataFim = $turma->data_fim?->format('Y-m-d') ?? '';
        $this->ativo = $turma->ativo;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'turma-form');
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();
        $ehAdmin = $user->hasRole('admin');
        $turmaAtual = $this->turmaId ? Turma::findOrFail($this->turmaId) : null;

        if ($turmaAtual) {
            Gate::authorize('update', $turmaAtual);
        }

        if ($ehAdmin) {
            $this->validate(['professorId' => 'required|exists:users,id']);

            if (! User::role('professor')->whereKey($this->professorId)->exists()) {
                $this->addError('professorId', 'Escolha um professor.');

                return;
            }
        } else {
            // Professor só cria turma nos cursos dele (ou mantém o curso que a turma já tinha).
            $cursoPermitido = Curso::visiveisPara($user)->whereKey($this->cursoId)->exists()
                || $turmaAtual?->curso_id === $this->cursoId;

            if (! $cursoPermitido) {
                $this->addError('cursoId', 'Escolha um dos seus cursos.');

                return;
            }
        }

        Turma::updateOrCreate(
            ['id' => $this->turmaId],
            [
                'curso_id' => $this->cursoId,
                'professor_id' => $ehAdmin ? $this->professorId : $user->id,
                'nome' => $this->nome,
                'data_inicio' => $this->dataInicio ?: null,
                'data_fim' => $this->dataFim ?: null,
                'ativo' => $this->ativo,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Turma salva com sucesso.');
    }

    public function delete(int $id): void
    {
        $turma = Turma::findOrFail($id);

        Gate::authorize('delete', $turma);

        $turma->delete();

        session()->flash('success', 'Turma removida.');
    }

    public function render()
    {
        $user = Auth::user();
        $ehAdmin = $user->hasRole('admin');

        $cursos = Curso::visiveisPara($user)->orderBy('nome')->get();

        // Editando turma de um curso que não é dele (ex.: atribuída pelo admin): o curso atual continua na lista.
        if (! $ehAdmin && $this->cursoId && ! $cursos->contains('id', $this->cursoId)) {
            $cursos->push(Curso::find($this->cursoId));
        }

        return view('livewire.academico.turma-manager', [
            'turmas' => Turma::with(['curso', 'professor'])->withCount('alunos')
                ->when(! $ehAdmin, fn ($q) => $q->doProfessor($user))
                ->orderBy('nome')->get(),
            'cursos' => $cursos->filter(),
            'ehAdmin' => $ehAdmin,
            'professores' => $ehAdmin ? User::role('professor')->orderBy('name')->get() : collect(),
        ]);
    }
}
