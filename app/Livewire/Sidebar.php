<?php

namespace App\Livewire;

use App\Models\MenuSideBar;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    #[On('menu-updated')]
    public function refresh(): void
    {
        // Re-render picks up the latest menu_side_bars state.
    }

    public function render()
    {
        $items = MenuSideBar::roots()->with('children')->get()->filter(fn ($item) => $this->visible($item));

        $items->each(function (MenuSideBar $item) {
            $item->setRelation('children', $item->children->filter(fn ($child) => $this->visible($child)));
        });

        return view('livewire.sidebar', ['groups' => $items->groupBy(fn ($item) => $item->group ?: '')]);
    }

    /**
     * Itens que são do dia a dia de professor/aluno ("Meus Alunos", "Minha Turma",
     * "Aulas ao Vivo"). O admin passa em qualquer permissão (Gate::before), mas
     * esses itens não fazem sentido pra ele — ele usa Cursos, Turmas e Usuários,
     * onde vê tudo. Esconder é só pra não poluir o menu; a URL continua liberada.
     */
    private const PERMISSOES_SO_DE_PROFESSOR_OU_ALUNO = ['manage-own-turmas', 'view-own-turma'];

    private function visible(MenuSideBar $item): bool
    {
        $user = Auth::user();

        if ($user?->hasRole('admin') && in_array($item->permission, self::PERMISSOES_SO_DE_PROFESSOR_OU_ALUNO, true)) {
            return false;
        }

        return ! $item->permission || $user?->can($item->permission);
    }
}
