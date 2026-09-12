<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_list_audit_logs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $coupon = Coupon::factory()->create();
        AuditLogger::log('coupon.create', $coupon, null, $coupon->toArray());

        $response = $this->getJson('/api/v1/admin/audit-logs');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.action', 'coupon.create');
    }

    public function test_a_moderator_cannot_view_audit_logs(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $this->actingAs($moderator)->getJson('/api/v1/admin/audit-logs')->assertStatus(403);
    }
}
