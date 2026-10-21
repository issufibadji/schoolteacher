<?php

namespace App\Livewire\Academico;

use App\Models\Turma;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Matrícula da turma. O professor só escolhe entre os alunos DELE (matriculados
 * em turmas dele ou cadastrados por ele) e cadastra aluno novo aqui mesmo, já
 * matriculando. O admin escolhe entre todos os alunos.
 */
#[Layout('components.layouts.master')]
class MatriculaManager extends Component
{
    public Turma $turma;

    public ?int $alunoId = null;

    public string $novoNome = '';

    public string $novoEmail = '';

    public string $novaSenha = '';

    public function mount(Turma $turma): void
    {
        Gate::authorize('manageMatricula', $turma);

        $this->turma = $turma;
    }

    public function matricular(): void
    {
        Gate::authorize('manageMatricula', $this->turma);

        $this->validate(['alunoId' => 'required|integer'], ['alunoId.required' => 'Selecione um aluno.']);

        // Só aluno que este usuário pode ver (impede matricular aluno de outro professor pelo id).
        abort_unless($this->candidatosQuery()->whereKey($this->alunoId)->exists(), 403);

        $this->matricularAluno($this->alunoId);

        $this->reset('alunoId');
        session()->flash('success', 'Aluno matriculado com sucesso.');
    }

    public function abrirNovoAluno(): void
    {
        $this->reset(['novoNome', 'novoEmail', 'novaSenha']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'novo-aluno');
    }

    public function criarAluno(): void
    {
        Gate::authorize('manageMatricula', $this->turma);

        $this->validate([
            'novoNome' => 'required|string|max:255',
            'novoEmail' => 'required|email|max:255|unique:users,email',
            'novaSenha' => 'required|string|min:8',
        ], [
            'novoEmail.unique' => 'Já existe uma conta com este e-mail. Se for aluno de outra turma, peça ao administrador para matriculá-lo.',
        ]);

        $aluno = new User;
        $aluno->forceFill([
            'name' => $this->novoNome,
            'email' => $this->novoEmail,
            'password' => Hash::make($this->novaSenha),
            'email_verified_at' => now(),
            'active' => true,
            'criado_por' => Auth::id(),
        ])->save();

        $aluno->assignRole('aluno');
        $aluno->notify(new UserAccountCreated);

        $this->matricularAluno($aluno->id);

        $this->dispatch('close-modal');
        session()->flash('success', "Aluno {$aluno->name} cadastrado e matriculado. Passe a ele o e-mail e a senha de acesso.");
    }

    public function desmatricular(int $alunoId): void
    {
        Gate::authorize('manageMatricula', $this->turma);

        $this->turma->alunos()->detach($alunoId);

        session()->flash('success', 'Matrícula removida.');
    }

    private function matricularAluno(int $alunoId): void
    {
        $this->turma->alunos()->syncWithoutDetaching([
            $alunoId => ['data_matricula' => now()->toDateString(), 'status' => 'ativo'],
        ]);
    }

    /**
     * Alunos que podem ser matriculados nesta turma por quem está logado.
     */
    private function candidatosQuery()
    {
        $user = Auth::user();

        return ($user->hasRole('admin') ? User::role('aluno') : User::alunosDoProfessor($user))
            ->whereNotIn('users.id', $this->turma->alunos()->pluck('users.id'));
    }

    public function render()
    {
        return view('livewire.academico.matricula-manager', [
            'alunos' => $this->turma->alunos()->orderBy('name')->get(),
            'candidatos' => $this->candidatosQuery()->orderBy('name')->get(),
            'ehAdmin' => Auth::user()->hasRole('admin'),
        ]);
    }
}
