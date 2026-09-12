<?php

namespace Tests\Feature\Admin;

use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportedListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_open_reports_are_listed_and_can_be_resolved(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');
        $reporter = User::factory()->create();
        $property = Property::factory()->create();
        $report = $property->reports()->create(['reported_by' => $reporter->id, 'reason' => 'Spam', 'status' => 'open']);

        $index = $this->actingAs($moderator)->getJson('/api/v1/admin/reported-listings');
        $index->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($moderator)->postJson("/api/v1/admin/reported-listings/{$report->id}/resolve")->assertOk();

        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame($moderator->id, $report->fresh()->resolved_by);
    }
}
