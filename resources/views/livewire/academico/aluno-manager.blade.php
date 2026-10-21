<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Meus Alunos</h1>

    <x-table :headers="['Nome', 'E-mail', 'Turma(s)']">
        @forelse ($alunos as $aluno)
            <tr>
                <td class="px-4 py-3">{{ $aluno->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $aluno->email }}</td>
                <td class="px-4 py-3">
                    @forelse ($aluno->turmasMatriculadas as $turma)
                        <x-badge variant="primary">{{ $turma->nome }}</x-badge>
                    @empty
                        <x-badge>Sem matrícula</x-badge>
                    @endforelse
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-4 py-6 text-center text-sm text-text-secondary">
                    Você ainda não tem alunos. Cadastre pela tela de Matrícula de uma das suas turmas.
                </td>
            </tr>
        @endforelse
    </x-table>
</div>
