<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\User;

/**
 * Curso é de quem criou (cursos.professor_id). O professor só vê/edita/exclui
 * os dele; o admin passa por tudo via Gate::before.
 */
class CursoPolicy
{
    public function view(User $user, Curso $curso): bool
    {
        return $this->ehDono($user, $curso);
    }

    public function update(User $user, Curso $curso): bool
    {
        return $this->ehDono($user, $curso);
    }

    public function delete(User $user, Curso $curso): bool
    {
        return $this->ehDono($user, $curso);
    }

    private function ehDono(User $user, Curso $curso): bool
    {
        return $user->hasRole('professor') && $curso->professor_id === $user->id;
    }
}
