<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
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

        // O professor cria e gerencia os próprios cursos, turmas, matrículas e
        // conteúdo. A permissão libera a TELA; o que ele enxerga dentro dela é só
        // o que é dele (scopes + CursoPolicy/TurmaPolicy). Só o admin vê tudo.
        foreach (['view-users', 'create-users', 'edit-users', 'delete-users'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $professor = Role::findOrCreate('professor');
        $professor->syncPermissions([
            'manage-cursos', 'manage-turmas', 'manage-matriculas', 'manage-own-turmas',
            // Tela "Usuários": só cadastra aluno e só mexe nos que cadastrou (UserPolicy).
            'view-users', 'create-users', 'edit-users', 'delete-users',
        ]);

        $aluno = Role::findOrCreate('aluno');
        $aluno->givePermissionTo('view-own-turma');
    }
}
