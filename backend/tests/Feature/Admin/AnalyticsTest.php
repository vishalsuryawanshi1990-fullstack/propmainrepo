<?php

namespace Tests\Feature\Admin;

use App\Models\ContactUnlock;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_listings_by_status_groups_correctly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Property::factory()->create(['status' => 'live']);
        Property::factory()->create(['status' => 'live']);
        Property::factory()->pendingReview()->create();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/analytics/listings-by-status');

        $counts = collect($response->json('data'))->pluck('count', 'status');
        $this->assertSame(2, $counts['live']);
        $this->assertSame(1, $counts['pending_review']);
    }

    public function test_revenue_sums_paid_payments_by_purpose(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Payment::factory()->create(['purpose' => 'coupon_purchase', 'status' => 'paid', 'amount' => 19]);
        Payment::factory()->create(['purpose' => 'coupon_purchase', 'status' => 'paid', 'amount' => 49]);
        Payment::factory()->create(['purpose' => 'coupon_purchase', 'status' => 'created', 'amount' => 99]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/analytics/revenue');

        $byPurpose = collect($response->json('data.by_purpose'))->keyBy('purpose');
        $this->assertEquals(68, $byPurpose['coupon_purchase']['total']);
        $this->assertSame(2, $byPurpose['coupon_purchase']['count']);
    }

    public function test_unlock_funnel_counts_distinct_users_per_stage(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $repeatUser = User::factory()->create();
        $property1 = Property::factory()->create();
        $property2 = Property::factory()->create();
        ContactUnlock::create(['unlocker_user_id' => $repeatUser->id, 'property_id' => $property1->id, 'credits_spent' => 1, 'unlocked_at' => now()]);
        ContactUnlock::create(['unlocker_user_id' => $repeatUser->id, 'property_id' => $property2->id, 'credits_spent' => 1, 'unlocked_at' => now()]);

        $wallet = Wallet::create(['user_id' => $repeatUser->id, 'contact_unlock_credits' => 1]);
        WalletTransaction::create(['wallet_id' => $wallet->id, 'type' => 'credit', 'source' => 'video_ad', 'amount' => 1]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/analytics/unlock-funnel');

        $response->assertOk();
        $this->assertSame(1, $response->json('data.used_free_unlock'));
        $this->assertSame(1, $response->json('data.repeat_unlockers'));
        $this->assertSame(1, $response->json('data.topped_up_via_video'));
    }
}
