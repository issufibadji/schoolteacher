<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">Usuários</h1>
            @unless ($ehAdmin)
                <p class="text-sm text-text-secondary">Seus alunos: os das suas turmas e os que você cadastrou.</p>
            @endunless
        </div>
        @can('create', \App\Models\User::class)
            <x-button wire:click="create">{{ $ehAdmin ? '+ Usuário' : '+ Aluno' }}</x-button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar nome ou e-mail..."
            class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm"
        >

        <select wire:model.live="statusFilter" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
            <option value="">Status</option>
            <option value="active">Ativo</option>
            <option value="inactive">Inativo</option>
        </select>

        <select wire:model.live="verifiedFilter" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
            <option value="">E-mail verificado</option>
            <option value="verified">Verificado</option>
            <option value="unverified">Não verificado</option>
        </select>

        @if ($ehAdmin)
            <select wire:model.live="roleFilter" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
                <option value="">Papel</option>
                @foreach ($allRoles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <x-table :headers="['Nome', 'E-mail', 'Papéis', 'Status', '2FA', 'Verificado', 'Ações']">
        @foreach ($users as $user)
            <tr>
                <td class="px-4 py-3">{{ $user->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $user->email }}</td>
                <td class="px-4 py-3">
                    @forelse ($user->roles as $role)
                        <x-badge>{{ $role->name }}</x-badge>
                    @empty
                        <span class="text-text-secondary">—</span>
                    @endforelse
                </td>
                <td class="px-4 py-3">
                    <x-badge :variant="$user->active ? 'success' : 'danger'">
                        {{ $user->active ? 'Ativo' : 'Inativo' }}
                    </x-badge>
                </td>
                <td class="px-4 py-3">
                    @if ($user->two_factor_confirmed_at)
                        <x-badge variant="success">Ativo</x-badge>
                    @elseif ($user->requires_2fa)
                        <x-badge variant="warning">Pendente</x-badge>
                    @else
                        <x-badge>Desativado</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-badge :variant="$user->email_verified_at ? 'success' : 'default'">
                        {{ $user->email_verified_at ? 'Sim' : 'Não' }}
                    </x-badge>
                </td>
                <td class="px-4 py-3">
                    {{-- Quebra em mais linhas quando não cabe, em vez de vazar a coluna --}}
                    <div class="flex flex-wrap justify-end gap-x-3 gap-y-1 min-w-36">
                    @if ($impersonation->podeAcessarComo(auth()->user(), $user))
                        <button
                            wire:click="acessarComo({{ $user->id }})"
                            wire:confirm="Acessar o sistema como {{ $user->name }}? Tudo que você fizer será em nome dessa conta (e registrado na auditoria)."
                            class="text-accent hover:underline text-sm"
                        >
                            Acessar como
                        </button>
                    @endif
                    @can('update', $user)
                        <button wire:click="edit({{ $user->id }})" class="text-primary hover:underline text-sm">Editar</button>
                        <button wire:click="toggleActive({{ $user->id }})" class="text-text-secondary hover:underline text-sm">
                            {{ $user->active ? 'Desativar' : 'Ativar' }}
                        </button>
                        @if ($ehAdmin)
                            <button wire:click="toggleRequires2fa({{ $user->id }})" class="text-text-secondary hover:underline text-sm">
                                {{ $user->requires_2fa ? 'Não exigir 2FA' : 'Exigir 2FA' }}
                            </button>
                        @endif
                    @endcan
                    @can('delete', $user)
                        <button
                            wire:click="delete({{ $user->id }})"
                            wire:confirm="Remover este usuário?"
                            class="text-danger hover:underline text-sm"
                        >
                            Excluir
                        </button>
                    @elsecan('update', $user)
                        {{-- Professor: aluno também está na turma de outro professor — não exclui a conta. --}}
                        <span class="text-xs text-text-secondary" title="Este aluno também está na turma de outro professor. Para tirá-lo da sua turma, use Desmatricular na tela de Matrícula.">
                            Compartilhado
                        </span>
                    @endcan
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <x-modal name="user-form" title="Usuário">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="name" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">E-mail</label>
                <input type="email" wire:model="email" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">
                    Senha {{ $userId ? '(deixe em branco para não alterar)' : '' }}
                </label>
                <input type="password" wire:model="password" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">{{ $ehAdmin ? 'Papéis' : 'Perfil' }}</label>
                @if ($ehAdmin)
                    <div class="space-y-1">
                        @foreach ($allRoles as $role)
                            <label class="flex items-center gap-2 text-sm text-text-secondary">
                                <input type="checkbox" wire:model="roles" value="{{ $role->name }}" class="rounded bg-surface border-surface-border">
                                {{ $role->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('roles.*') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @else
                    {{-- Professor só cadastra aluno; o servidor força esse perfil. --}}
                    <div class="flex items-center gap-2">
                        <x-badge variant="primary">Aluno</x-badge>
                        <span class="text-xs text-text-secondary">Professores só cadastram alunos.</span>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
