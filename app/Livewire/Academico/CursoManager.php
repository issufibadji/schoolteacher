<?php

namespace App\Livewire\Academico;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Professor: cria e gerencia só os próprios cursos (vira o dono ao criar).
 * Admin: vê todos e escolhe o professor responsável (ou deixa sem, = do admin).
 */
#[Layout('components.layouts.master')]
class CursoManager extends Component
{
    public ?int $cursoId = null;

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('required|in:longo_prazo,curto_prazo')]
    public string $tipo = 'longo_prazo';

    #[Validate('nullable|string')]
    public string $descricao = '';

    public bool $ativo = true;

    /** Só o admin escolhe; para o professor é sempre ele mesmo. */
    public ?int $professorId = null;

    public function create(): void
    {
        $this->reset(['cursoId', 'nome', 'tipo', 'descricao', 'professorId']);
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'curso-form');
    }

    public function edit(int $id): void
    {
        $curso = Curso::findOrFail($id);

        Gate::authorize('update', $curso);

        $this->cursoId = $curso->id;
        $this->nome = $curso->nome;
        $this->tipo = $curso->tipo;
        $this->descricao = $curso->descricao ?? '';
        $this->ativo = $curso->ativo;
        $this->professorId = $curso->professor_id;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'curso-form');
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();
        $ehAdmin = $user->hasRole('admin');

        if ($this->cursoId) {
            Gate::authorize('update', Curso::findOrFail($this->cursoId));
        }

        if ($ehAdmin && $this->professorId && ! User::role('professor')->whereKey($this->professorId)->exists()) {
            $this->addError('professorId', 'Escolha um professor válido.');

            return;
        }

        Curso::updateOrCreate(
            ['id' => $this->cursoId],
            [
                'nome' => $this->nome,
                'tipo' => $this->tipo,
                'descricao' => $this->descricao ?: null,
                'ativo' => $this->ativo,
                'professor_id' => $ehAdmin ? $this->professorId : $user->id,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Curso salvo com sucesso.');
    }

    public function delete(int $id): void
    {
        $curso = Curso::findOrFail($id);

        Gate::authorize('delete', $curso);

        $curso->delete();

        session()->flash('success', 'Curso removido.');
    }

    public function render()
    {
        $user = Auth::user();
        $ehAdmin = $user->hasRole('admin');

        return view('livewire.academico.curso-manager', [
            'cursos' => Curso::visiveisPara($user)->with('professor')->withCount('turmas')->orderBy('nome')->get(),
            'ehAdmin' => $ehAdmin,
            'professores' => $ehAdmin ? User::role('professor')->orderBy('name')->get() : collect(),
        ]);
    }
}
