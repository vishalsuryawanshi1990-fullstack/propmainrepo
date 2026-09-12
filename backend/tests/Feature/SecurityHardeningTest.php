<?php

namespace Tests\Feature;

use App\Models\CityMaster;
use App\Models\LocalityMaster;
use App\Models\PropertyTypeMaster;
use App\Models\User;
use App\Services\Otp\OtpGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Fakes\FakeOtpGateway;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_script_tag_in_a_property_description_is_stripped(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $type = PropertyTypeMaster::factory()->create();
        $city = CityMaster::factory()->create();
        $locality = LocalityMaster::factory()->create(['city_id' => $city->id]);

        $response = $this->actingAs($seller)->postJson('/api/v1/properties', [
            'title' => 'Test',
            'description' => '<p>Nice place</p><script>alert(1)</script>',
            'property_type_id' => $type->id,
            'listing_type' => 'rent',
            'price' => 10000,
            'city_id' => $city->id,
            'locality_id' => $locality->id,
            'address' => '1 Test St',
            'latitude' => 1,
            'longitude' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertStringNotContainsString('<script>', $response->json('data.description'));
        $this->assertStringContainsString('Nice place', $response->json('data.description'));
    }

    public function test_hsts_header_is_present_in_production_but_not_testing(): void
    {
        $this->getJson('/api/v1/ping')->assertHeaderMissing('Strict-Transport-Security');

        app()->detectEnvironment(fn () => 'production');
        $this->getJson('/api/v1/ping')->assertHeader('Strict-Transport-Security');
    }

    public function test_an_active_users_token_works(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me')->assertOk();
    }

    public function test_a_suspended_users_token_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me')->assertStatus(403);
    }

    public function test_too_many_new_accounts_from_the_same_device_are_blocked(): void
    {
        $this->app->bind(OtpGateway::class, FakeOtpGateway::class);
        config(['security.max_signups_per_device_per_day' => 1]);
        User::factory()->create(['device_id' => 'shared-device']);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111100002']);
        $otp = FakeOtpGateway::$sent['9111100002'];

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '9111100002',
            'otp' => $otp,
            'device_id' => 'shared-device',
        ]);

        $response->assertStatus(429);
        $this->assertDatabaseMissing('users', ['phone' => '9111100002']);
    }

    public function test_kyc_download_is_audit_logged(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');
        $applicant = User::factory()->create();
        $document = $applicant->kycDocuments()->create([
            'doc_type' => 'pan', 'file_path' => 'kyc/1/doc.pdf', 'status' => 'pending',
        ]);

        Storage::disk('local')->put($document->file_path, 'fake-bytes');

        $url = URL::temporarySignedRoute(
            'admin.kyc.download',
            now()->addMinutes(10),
            ['kycDocument' => $document->id],
        );

        $this->actingAs($moderator)->get($url)->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'kyc.download',
            'subject_id' => $document->id,
        ]);
    }

    public function test_kyc_file_path_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $document = $user->kycDocuments()->create([
            'doc_type' => 'pan', 'file_path' => 'kyc/1/plainly-named-file.pdf', 'status' => 'pending',
        ]);

        $raw = DB::table('kyc_documents')->find($document->id);

        $this->assertStringNotContainsString('plainly-named-file.pdf', $raw->file_path);
        $this->assertSame('kyc/1/plainly-named-file.pdf', $document->fresh()->file_path);
    }
}
