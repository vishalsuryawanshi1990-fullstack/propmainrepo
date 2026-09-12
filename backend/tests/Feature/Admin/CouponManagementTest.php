<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_non_admin_cannot_manage_coupons(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $this->actingAs($seller)->postJson('/api/v1/admin/coupons', [
            'code' => 'X', 'type' => 'fixed_credits', 'value' => 1, 'price' => 1,
        ])->assertStatus(403);
    }

    public function test_an_admin_can_create_update_and_delete_a_coupon(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $create = $this->actingAs($admin)->postJson('/api/v1/admin/coupons', [
            'code' => 'BULK10', 'type' => 'fixed_credits', 'value' => 10, 'price' => 49,
        ]);
        $create->assertStatus(201);
        $couponId = $create->json('data.id');

        $this->actingAs($admin)->patchJson("/api/v1/admin/coupons/{$couponId}", ['price' => 39])
            ->assertOk()->assertJsonPath('data.price', 39);

        $this->actingAs($admin)->deleteJson("/api/v1/admin/coupons/{$couponId}")->assertOk();
        $this->assertSoftDeleted(Coupon::class, ['id' => $couponId]);

        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'coupon.create']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'coupon.update']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'coupon.delete']);
    }
}
