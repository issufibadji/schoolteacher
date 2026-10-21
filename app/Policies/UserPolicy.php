<?php

namespace App\Policies;

use App\Models\User;

/**
 * Tela "Usuários". O admin passa por tudo via Gate::before. O professor só
 * cadastra ALUNOS (o perfil é forçado em UserManager) e só altera/exclui os
 * alunos que ele mesmo cadastrou — aluno criado pelo admin, que pode estar na
 * turma de outro professor, ele só vê.
 */
class UserPolicy
{
    public function view(User $user, User $alvo): bool
    {
        return $user->hasRole('professor')
            && User::alunosDoProfessor($user)->whereKey($alvo->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('professor');
    }

    public function update(User $user, User $alvo): bool
    {
        return $this->ehAlunoCadastradoPor($user, $alvo);
    }

    public function delete(User $user, User $alvo): bool
    {
        return $this->ehAlunoCadastradoPor($user, $alvo);
    }

    private function ehAlunoCadastradoPor(User $professor, User $alvo): bool
    {
        return $professor->hasRole('professor')
            && $alvo->criado_por === $professor->id
            && $alvo->getRoleNames()->all() === ['aluno'];
    }
}
