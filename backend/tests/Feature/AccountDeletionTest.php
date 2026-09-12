<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_account_purges_kyc_files_and_revokes_tokens(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $document = $user->kycDocuments()->create(['doc_type' => 'pan', 'file_path' => 'kyc/x/doc.pdf', 'status' => 'pending']);
        Storage::disk('local')->put($document->file_path, 'bytes');

        $this->withHeader('Authorization', "Bearer {$token}")->deleteJson('/api/v1/me')->assertOk();

        Storage::disk('local')->assertMissing($document->file_path);
        $this->assertSoftDeleted(User::class, ['id' => $user->id]);
        $this->assertSame(0, $user->tokens()->count());
    }
}
