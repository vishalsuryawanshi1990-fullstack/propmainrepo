<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MonitoringGatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_an_admin_can_view_horizon(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $this->assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($moderator)->allows('viewHorizon'));
    }

    public function test_only_an_admin_can_view_telescope(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $this->assertTrue(Gate::forUser($admin)->allows('viewTelescope'));
        $this->assertFalse(Gate::forUser($seller)->allows('viewTelescope'));
    }
}
