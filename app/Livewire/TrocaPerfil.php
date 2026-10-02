<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Seletor de perfil no topo, só pra quem acumula perfis alternáveis
 * (ex.: diretor que também é professor). Trocar volta pro dashboard, já que
 * a tela atual pode não existir no outro perfil.
 */
class TrocaPerfil extends Component
{
    public function trocar(string $perfil): void
    {
        $user = Auth::user();

        $user->trocarPerfil($perfil);

        session()->flash('success', 'Agora você está como '.User::rotuloPerfil($perfil).'.');

        $this->redirectRoute('dashboard', navigate: false);
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.troca-perfil', [
            'perfis' => $user->podeTrocarPerfil() ? $user->perfisAlternaveis() : [],
            'ativo' => $user->perfilAtivo(),
        ]);
    }
}
