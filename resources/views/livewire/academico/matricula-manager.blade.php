<div>
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('academico.turmas.index') }}" class="text-sm text-primary hover:underline">&larr; Turmas</a>
            <h1 class="text-2xl font-semibold text-text-primary mt-1">{{ $turma->nome }}</h1>
            <p class="text-sm text-text-secondary">Matrícula de alunos</p>
        </div>
        <x-button wire:click="abrirNovoAluno">+ Novo aluno</x-button>
    </div>

    <x-card class="mb-6">
        <form wire:submit="matricular" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-60">
                <label for="matricula-aluno" class="block text-sm font-medium mb-1 text-text-primary">Matricular aluno já cadastrado</label>
                <select id="matricula-aluno" wire:model="alunoId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">{{ $candidatos->isEmpty() ? 'Nenhum aluno disponível' : 'Selecione um aluno' }}</option>
                    @foreach ($candidatos as $candidato)
                        <option value="{{ $candidato->id }}">{{ $candidato->name }} ({{ $candidato->email }})</option>
                    @endforeach
                </select>
                @error('alunoId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @unless ($ehAdmin)
                    <p class="mt-1 text-xs text-text-secondary">Aparecem só os seus alunos (das suas turmas ou cadastrados por você). Aluno novo? Use "+ Novo aluno".</p>
                @endunless
            </div>
            <x-button type="submit" :disabled="$candidatos->isEmpty()">Matricular</x-button>
        </form>
    </x-card>

    <x-table :headers="['Nome', 'E-mail', 'Data matrícula', 'Status', 'Ações']">
        @forelse ($alunos as $aluno)
            <tr>
                <td class="px-4 py-3">{{ $aluno->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $aluno->email }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ \Illuminate\Support\Carbon::parse($aluno->pivot->data_matricula)->format('d/m/Y') }}</td>
                <td class="px-4 py-3"><x-badge variant="primary">{{ $aluno->pivot->status }}</x-badge></td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button
                        wire:click="desmatricular({{ $aluno->id }})"
                        wire:confirm="Remover a matrícula deste aluno?"
                        class="text-danger hover:underline text-sm"
                    >
                        Desmatricular
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-6 text-center text-sm text-text-secondary">Nenhum aluno matriculado nesta turma ainda.</td>
            </tr>
        @endforelse
    </x-table>

    <x-modal name="novo-aluno" title="Novo aluno">
        <form wire:submit="criarAluno" class="space-y-4">
            <p class="text-sm text-text-secondary">A conta é criada e o aluno já fica matriculado em <strong class="text-text-primary">{{ $turma->nome }}</strong>.</p>

            <div>
                <label for="novo-nome" class="block text-sm font-medium mb-1 text-text-primary">Nome completo</label>
                <input id="novo-nome" type="text" wire:model="novoNome" autocomplete="off" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('novoNome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="novo-email" class="block text-sm font-medium mb-1 text-text-primary">E-mail (login do aluno)</label>
                <input id="novo-email" type="email" wire:model="novoEmail" autocomplete="off" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('novoEmail') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nova-senha" class="block text-sm font-medium mb-1 text-text-primary">Senha inicial</label>
                <input id="nova-senha" type="text" wire:model="novaSenha" autocomplete="new-password" class="w-full rounded-md bg-surface border-surface-border text-text-primary font-mono">
                <p class="mt-1 text-xs text-text-secondary">Mínimo 8 caracteres. Passe ao aluno junto com o e-mail; ele pode trocar depois em "Meu Perfil".</p>
                @error('novaSenha') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit" wire:loading.attr="disabled" wire:target="criarAluno">Cadastrar e matricular</x-button>
            </div>
        </form>
    </x-modal>
</div>
