<div>
    @if ($perfis)
        <div class="flex items-center gap-1 p-1 rounded-full bg-surface-card border border-surface-border" role="group" aria-label="Trocar perfil">
            @foreach ($perfis as $perfil)
                <button
                    type="button"
                    @if ($perfil !== $ativo) wire:click="trocar('{{ $perfil }}')" @endif
                    wire:loading.attr="disabled"
                    aria-pressed="{{ $perfil === $ativo ? 'true' : 'false' }}"
                    aria-label="{{ \App\Models\User::rotuloPerfil($perfil) }}"
                    title="{{ \App\Models\User::rotuloPerfil($perfil) }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 pointer-coarse:min-h-11 pointer-coarse:min-w-11 rounded-full text-xs font-medium whitespace-nowrap transition {{ $perfil === $ativo ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:text-text-primary hover:bg-surface-border/60' }}"
                >
                    @if ($perfil === 'manager')
                        <x-heroicon-o-briefcase class="w-4 h-4 shrink-0" />
                    @else
                        <x-heroicon-o-academic-cap class="w-4 h-4 shrink-0" />
                    @endif
                    {{-- < 480px só o ícone; o nome fica no aria-label/title --}}
                    <span class="max-[479px]:hidden">{{ \App\Models\User::rotuloPerfil($perfil) }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>
