<div>
    @if ($perfis)
        <div class="flex items-center gap-1 p-1 rounded-full bg-surface-card border border-surface-border" role="group" aria-label="Trocar perfil">
            @foreach ($perfis as $perfil)
                <button
                    type="button"
                    @if ($perfil !== $ativo) wire:click="trocar('{{ $perfil }}')" @endif
                    wire:loading.attr="disabled"
                    aria-pressed="{{ $perfil === $ativo ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition {{ $perfil === $ativo ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:text-text-primary hover:bg-surface-border/60' }}"
                >
                    @if ($perfil === 'manager')
                        <x-heroicon-o-briefcase class="w-4 h-4" />
                    @else
                        <x-heroicon-o-academic-cap class="w-4 h-4" />
                    @endif
                    {{ \App\Models\User::rotuloPerfil($perfil) }}
                </button>
            @endforeach
        </div>
    @endif
</div>
