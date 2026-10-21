<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        $permissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-menu',
            'manage-roles',
            'manage-permissions',
            'manage-config',
            'view-audits',
            'send-notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Gestão de usuários e do sistema é só do admin. Papéis: admin, professor,
        // aluno (os dois últimos vêm do AcademicoPermissionSeeder).
        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);
    }
}
