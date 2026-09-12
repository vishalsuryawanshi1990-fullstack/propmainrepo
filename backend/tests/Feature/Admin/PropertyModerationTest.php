<?php

namespace Tests\Feature\Admin;

use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_pending_lists_only_pending_review_properties(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Property::factory()->pendingReview()->create();
        Property::factory()->create(['status' => 'live']);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/properties/pending');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_approving_makes_a_property_live_and_notifies_the_owner(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $property = Property::factory()->pendingReview()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/properties/{$property->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'live');
        $this->assertDatabaseHas('notifications', ['user_id' => $property->owner_id, 'type' => 'property.approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property.approve', 'actor_id' => $admin->id]);
    }

    public function test_rejecting_requires_a_reason_and_notifies_the_owner(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $property = Property::factory()->pendingReview()->create();

        $this->actingAs($admin)->postJson("/api/v1/admin/properties/{$property->id}/reject", [])
            ->assertStatus(422);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/properties/{$property->id}/reject", [
            'reason' => 'Photos are unclear.',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('notifications', ['user_id' => $property->owner_id, 'type' => 'property.rejected']);
    }

    public function test_a_seller_cannot_moderate_listings(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');
        $property = Property::factory()->pendingReview()->create();

        $this->actingAs($seller)->postJson("/api/v1/admin/properties/{$property->id}/approve")
            ->assertStatus(403);
    }
}
