<?php

namespace Tests\Feature\Rbac;

use App\Livewire\Admin\RoleUserLinker;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleUserLinkerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_assign_and_remove_a_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(RoleUserLinker::class)
            ->set("selectedRole.{$target->id}", 'admin')
            ->call('assign', $target->id);

        $this->assertTrue($target->fresh()->hasRole('admin'));

        Livewire::actingAs($admin)
            ->test(RoleUserLinker::class)
            ->call('remove', $target->id, 'admin');

        $this->assertFalse($target->fresh()->hasRole('admin'));
    }
}
