<?php

namespace Tests\Feature\Admin;

use App\Models\ScratchRewardMaster;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScratchRewardManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_index_reports_odds_percentages_across_active_rewards(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        ScratchRewardMaster::create(['reward_type' => 'none', 'value' => 0, 'probability_weight' => 90, 'is_active' => true]);
        ScratchRewardMaster::create(['reward_type' => 'bonus_credit', 'value' => 1, 'probability_weight' => 10, 'is_active' => true]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/scratch-rewards');

        $response->assertOk();
        $odds = collect($response->json('data'))->pluck('odds_percent', 'reward_type');
        $this->assertEquals(90.0, $odds['none']);
        $this->assertEquals(10.0, $odds['bonus_credit']);
    }

    public function test_cash_like_reward_types_are_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/admin/scratch-rewards', [
            'reward_type' => 'cash_withdrawal', 'value' => 100, 'probability_weight' => 1,
        ])->assertStatus(422);
    }

    public function test_an_admin_can_update_reward_odds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $reward = ScratchRewardMaster::create(['reward_type' => 'bonus_credit', 'value' => 1, 'probability_weight' => 5, 'is_active' => true]);

        $this->actingAs($admin)->patchJson("/api/v1/admin/scratch-rewards/{$reward->id}", ['probability_weight' => 20])
            ->assertOk();

        $this->assertSame(20, $reward->fresh()->probability_weight);
    }
}
