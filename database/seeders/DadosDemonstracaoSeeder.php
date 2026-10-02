<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DadosDemonstracaoSeeder extends Seeder
{
    /**
     * Volume de dados com nomes longos pra testar layout (tabelas, cards,
     * responsividade) em ambiente local. Depende do TestUsersSeeder
     * (professor@teste.com e diretor@teste.com). Só roda em local.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $professor = User::where('email', 'professor@teste.com')->first();
        $diretor = User::where('email', 'diretor@teste.com')->first();

        if (! $professor || ! $diretor) {
            $this->command?->warn('DadosDemonstracaoSeeder: rode o TestUsersSeeder antes.');

            return;
        }

        $alunos = collect([
            'Maria Eduarda Albuquerque de Vasconcelos Figueiredo',
            'João Pedro Montenegro Cavalcanti',
            'Ana Beatriz dos Santos Oliveira',
            'Luiz Fernando Bittencourt Guimarães Filho',
            'Carla Cristina Nogueira',
            'Rafael Augusto Schneider Wolff',
            'Fernanda Lima',
            'Gabriel Henrique Albuquerque Maranhão',
        ])->map(fn (string $nome, int $i) => $this->usuario($nome, "aluno.demonstracao.{$i}.com.nome.longo@escola-de-idiomas-exemplo.com.br", 'aluno'));

        $this->usuario('Professora Mariana Rodrigues Valadares Bastos', 'mariana.valadares.bastos@escola-de-idiomas-exemplo.com.br', 'professor');

        $cursos = collect([
            ['Inglês para Negócios e Comunicação Corporativa Internacional', 'longo_prazo'],
            ['Preparatório Intensivo para Certificação TOEFL iBT', 'curto_prazo'],
            ['Conversação Avançada', 'longo_prazo'],
        ])->map(fn (array $c) => Curso::updateOrCreate(
            ['nome' => $c[0]],
            ['tipo' => $c[1], 'descricao' => 'Curso de demonstração com nome longo.', 'ativo' => true],
        ));

        $turmas = [
            [$cursos[0], $professor, 'Turma Noturna Executivos — Segunda e Quarta 19h às 21h'],
            [$cursos[1], $professor, 'Turma Sábado Manhã'],
            [$cursos[2], $diretor, 'Turma Avançada do Diretor — Terças e Quintas'],
        ];

        foreach ($turmas as $i => [$curso, $dono, $nome]) {
            $turma = Turma::updateOrCreate(
                ['curso_id' => $curso->id, 'nome' => $nome],
                ['professor_id' => $dono->id, 'data_inicio' => now()->subDays(20)->toDateString(), 'ativo' => true],
            );

            $turma->alunos()->syncWithoutDetaching(
                $alunos->slice($i * 3, 4)->mapWithKeys(fn (User $aluno) => [
                    $aluno->id => ['data_matricula' => now()->subDays(20)->toDateString(), 'status' => 'ativo'],
                ])->all(),
            );
        }
    }

    private function usuario(string $nome, string $email, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $nome,
            'password' => $user->password ?? Hash::make('password'),
            'email_verified_at' => now(),
            'active' => true,
        ])->save();

        $user->syncRoles([$role]);

        return $user;
    }
}
