<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    public function test_presign_returns_a_local_direct_upload_url_when_no_s3_is_configured(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->getJson(
            "/api/v1/properties/{$property->id}/image/presigned-url?extension=jpg&content_type=image/jpeg"
        );

        $response->assertOk();
        $this->assertSame('POST', $response->json('data.method'));
        $this->assertStringStartsWith("properties/{$property->id}/images/", $response->json('data.path'));
    }

    public function test_a_stranger_cannot_presign_an_upload_for_someone_elses_property(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $stranger = User::factory()->create();
        $stranger->assignRole('seller');
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->getJson("/api/v1/properties/{$property->id}/image/presigned-url?extension=jpg&content_type=image/jpeg")
            ->assertStatus(403);
    }

    public function test_full_local_upload_then_attach_flow(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $presign = $this->actingAs($owner)->getJson(
            "/api/v1/properties/{$property->id}/image/presigned-url?extension=jpg&content_type=image/jpeg"
        )->json('data');

        $this->actingAs($owner)
            ->call('POST', $presign['upload_url'], [], [], [], [], 'fake-image-bytes')
            ->assertOk();

        Storage::disk('public')->assertExists($presign['path']);

        $attach = $this->actingAs($owner)->postJson("/api/v1/properties/{$property->id}/image", [
            'path' => $presign['path'],
            'is_primary' => true,
        ]);

        $attach->assertStatus(201)->assertJsonPath('data.is_primary', true);
        $this->assertSame(1, $property->images()->count());
    }

    public function test_attaching_a_path_outside_the_property_is_rejected(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $otherProperty = Property::factory()->create();

        $response = $this->actingAs($owner)->postJson("/api/v1/properties/{$property->id}/image", [
            'path' => "properties/{$otherProperty->id}/images/evil.jpg",
        ]);

        $response->assertStatus(422);
    }
}
