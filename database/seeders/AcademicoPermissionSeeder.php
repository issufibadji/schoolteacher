<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicoPermissionSeeder extends Seeder
{
    /**
     * Seed the roles and permissions for the Domínio Acadêmico.
     */
    public function run(): void
    {
        $permissions = [
            'manage-cursos',
            'manage-turmas',
            'manage-matriculas',
            'manage-own-turmas',
            'view-own-turma',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo($permissions);

        // Manager é um perfil acima do professor (tipo diretor) — gerencia
        // cursos, turmas e matrículas de qualquer professor. Matrícula é
        // exclusiva do manager: um professor "puro" não matricula/desmatricula
        // aluno — só gerencia módulo/conteúdo da própria turma (manage-own-turmas).
        // Manager que também dá aula recebe o papel professor e alterna entre
        // os dois perfis no topo da tela (User::PERFIS_ALTERNAVEIS).
        $manager = Role::findOrCreate('manager');
        $manager->givePermissionTo(['manage-cursos', 'manage-turmas', 'manage-matriculas']);
        $manager->revokePermissionTo('manage-own-turmas');

        $professor = Role::findOrCreate('professor');
        $professor->givePermissionTo('manage-own-turmas');

        // Managers que já têm turma própria (de antes da troca de perfil)
        // ganham o papel professor, pra não perderem acesso às turmas.
        User::role('manager')
            ->whereHas('turmasComoProfessor')
            ->get()
            ->each(fn (User $user) => $user->assignRole('professor'));

        $aluno = Role::findOrCreate('aluno');
        $aluno->givePermissionTo('view-own-turma');
    }
}
