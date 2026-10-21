<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

/**
 * Professor só mexe nas turmas em que é o responsável (professor_id); aluno só
 * vê as turmas em que está matriculado. O admin passa por tudo via Gate::before.
 */
class TurmaPolicy
{
    public function view(User $user, Turma $turma): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('professor') && $turma->professor_id === $user->id) {
            return true;
        }

        if ($user->hasRole('aluno')) {
            return $turma->alunos()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function update(User $user, Turma $turma): bool
    {
        return $this->ehResponsavel($user, $turma);
    }

    public function delete(User $user, Turma $turma): bool
    {
        return $this->ehResponsavel($user, $turma);
    }

    /**
     * Gerenciar módulos/conteúdo (material) da turma.
     */
    public function manageConteudo(User $user, Turma $turma): bool
    {
        return $this->ehResponsavel($user, $turma);
    }

    /**
     * Matricular/desmatricular alunos — o professor da turma (ou o admin).
     */
    public function manageMatricula(User $user, Turma $turma): bool
    {
        return $this->ehResponsavel($user, $turma);
    }

    private function ehResponsavel(User $user, Turma $turma): bool
    {
        return $user->hasRole('admin') || $turma->professor_id === $user->id;
    }
}
