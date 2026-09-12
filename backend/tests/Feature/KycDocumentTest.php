<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_upload_a_kyc_document(): void
    {
        $response = $this->postJson('/api/v1/me/kyc-documents', [
            'doc_type' => 'pan',
            'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(401);
    }

    public function test_a_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/me/kyc-documents', [
            'doc_type' => 'pan',
            'file' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_a_valid_upload_is_stored_privately_and_marked_pending(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/me/kyc-documents', [
            'doc_type' => 'aadhaar',
            'file' => UploadedFile::fake()->create('aadhaar.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201)->assertJsonPath('data.status', 'pending');

        $document = $user->kycDocuments()->sole();
        $this->assertSame('aadhaar', $document->doc_type);
        Storage::disk('local')->assertExists($document->file_path);

        // The uploader is not a reviewer, so no download URL is leaked to them.
        $response->assertJsonPath('data.download_url', null);
    }

    public function test_a_user_only_sees_their_own_documents(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->kycDocuments()->create(['doc_type' => 'pan', 'file_path' => 'kyc/x.pdf', 'status' => 'pending']);

        $response = $this->actingAs($other)->getJson('/api/v1/me/kyc-documents');

        $response->assertOk()->assertJsonCount(0, 'data');
    }
}
