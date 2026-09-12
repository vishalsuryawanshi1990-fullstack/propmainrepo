<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        User::factory()->create(['name' => 'Findable Person']);
        User::factory()->create(['name' => 'Someone Else']);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/users?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_suspend_a_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/users/{$target->id}/status", ['status' => 'suspended']);

        $response->assertOk()->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.status', 'actor_id' => $admin->id]);
    }
}
