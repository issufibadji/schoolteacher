<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Cursos</h1>
        <x-button wire:click="create">+ Curso</x-button>
    </div>

    <x-table :headers="['Nome', 'Responsável', 'Tipo', 'Turmas', 'Ativo', 'Ações']">
        @forelse ($cursos as $curso)
            <tr>
                <td class="px-4 py-3">{{ $curso->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">
                    @if ($curso->professor_id === auth()->id())
                        <x-badge variant="primary">Atribuído a mim</x-badge>
                    @else
                        {{ $curso->professor?->name ?? 'Administração' }}
                    @endif
                </td>
                <td class="px-4 py-3 text-text-secondary">{{ $curso->tipo === 'longo_prazo' ? 'Longo prazo' : 'Curto prazo' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $curso->turmas_count }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$curso->ativo ? 'success' : 'default'">{{ $curso->ativo ? 'Sim' : 'Não' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $curso->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $curso->id }})"
                        wire:confirm="Remover este curso?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-6 text-center text-sm text-text-secondary">
                    {{ $ehAdmin ? 'Nenhum curso cadastrado.' : 'Você ainda não tem cursos. Clique em "+ Curso" para criar o primeiro.' }}
                </td>
            </tr>
        @endforelse
    </x-table>

    <x-modal name="curso-form" title="Curso">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="nome" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('nome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            @if ($ehAdmin)
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Professor responsável</label>
                    <select wire:model="professorId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        <option value="">Administração (sem professor)</option>
                        @foreach ($professores as $professor)
                            <option value="{{ $professor->id }}">{{ $professor->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-text-secondary">Só o professor responsável vê e cria turmas neste curso.</p>
                    @error('professorId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @else
                {{-- Professor: o curso é sempre dele (o servidor grava o próprio professor como dono). --}}
                <div>
                    <label for="curso-responsavel" class="block text-sm font-medium mb-1 text-text-primary">Professor responsável</label>
                    <select id="curso-responsavel" disabled class="w-full rounded-md bg-surface border-surface-border text-text-primary opacity-80 cursor-not-allowed">
                        <option selected>Atribuído a mim — {{ auth()->user()->name }}</option>
                    </select>
                    <p class="mt-1 text-xs text-text-secondary">Os cursos que você cria ficam com você: só você vê e cria turmas neles.</p>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Tipo</label>
                <select wire:model="tipo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="longo_prazo">Longo prazo</option>
                    <option value="curto_prazo">Curto prazo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Descrição</label>
                <textarea wire:model="descricao" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="ativo" class="rounded bg-surface border-surface-border">
                Ativo
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
