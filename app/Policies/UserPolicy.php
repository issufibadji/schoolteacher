<?php

namespace App\Policies;

use App\Models\User;

/**
 * Tela "Usuários". O admin passa por tudo via Gate::before. O professor só
 * cadastra ALUNOS (o perfil é forçado em UserManager) e edita/exclui os alunos
 * DELE (das turmas dele ou cadastrados por ele) — nunca professor ou admin.
 * Excluir tem uma trava a mais: aluno que também está na turma de outro
 * professor não é excluído (sumiria da turma do colega); aí ele só desmatricula.
 */
class UserPolicy
{
    public function view(User $user, User $alvo): bool
    {
        return $this->ehAlunoDoProfessor($user, $alvo);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('professor');
    }

    public function update(User $user, User $alvo): bool
    {
        return $this->ehAlunoDoProfessor($user, $alvo);
    }

    public function delete(User $user, User $alvo): bool
    {
        return $this->ehAlunoDoProfessor($user, $alvo) && ! $this->estaEmTurmaDeOutroProfessor($user, $alvo);
    }

    private function ehAlunoDoProfessor(User $professor, User $alvo): bool
    {
        return $professor->hasRole('professor')
            && $alvo->getRoleNames()->all() === ['aluno']
            && User::alunosDoProfessor($professor)->whereKey($alvo->id)->exists();
    }

    public function estaEmTurmaDeOutroProfessor(User $professor, User $alvo): bool
    {
        return $alvo->turmasMatriculadas()->where('professor_id', '!=', $professor->id)->exists();
    }
}
