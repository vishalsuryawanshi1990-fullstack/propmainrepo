<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KycReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_buyer_cannot_access_kyc_review_routes(): void
    {
        $buyer = User::factory()->create();
        $buyer->assignRole('buyer');

        $response = $this->actingAs($buyer)->getJson('/api/v1/admin/kyc/pending');

        $response->assertStatus(403);
    }

    public function test_a_moderator_can_verify_a_pending_document(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $applicant = User::factory()->create();
        $document = $applicant->kycDocuments()->create([
            'doc_type' => 'aadhaar',
            'file_path' => 'kyc/1/doc.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($moderator)->postJson("/api/v1/admin/kyc/{$document->id}/verify");

        $response->assertOk()->assertJsonPath('data.status', 'verified');

        $document->refresh();
        $this->assertSame('verified', $document->status);
        $this->assertSame($moderator->id, $document->verified_by);

        $this->assertDatabaseHas(AuditLog::class, [
            'actor_id' => $moderator->id,
            'action' => 'kyc.verify',
            'subject_type' => KycDocument::class,
            'subject_id' => $document->id,
        ]);
    }

    public function test_rejecting_a_document_requires_a_reason(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $document = User::factory()->create()->kycDocuments()->create([
            'doc_type' => 'pan',
            'file_path' => 'kyc/2/doc.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($moderator)->postJson("/api/v1/admin/kyc/{$document->id}/reject", []);
        $response->assertStatus(422);

        $response = $this->actingAs($moderator)->postJson("/api/v1/admin/kyc/{$document->id}/reject", [
            'rejection_reason' => 'Document is blurry.',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertSame('Document is blurry.', $document->fresh()->rejection_reason);
    }
}
