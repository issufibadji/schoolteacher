<?php

namespace App\Livewire\Academico;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * "Meus Alunos": só os alunos do professor logado — das turmas dele ou
 * cadastrados por ele —, com as turmas DELE em que cada um está.
 */
#[Layout('components.layouts.master')]
class AlunoManager extends Component
{
    public function render()
    {
        $professor = Auth::user();

        $alunos = User::alunosDoProfessor($professor)
            ->with(['turmasMatriculadas' => fn ($query) => $query->where('professor_id', $professor->id)])
            ->orderBy('name')
            ->get();

        return view('livewire.academico.aluno-manager', [
            'alunos' => $alunos,
        ]);
    }
}
