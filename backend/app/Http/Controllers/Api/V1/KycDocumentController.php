<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycDocumentRequest;
use App\Http\Resources\KycDocumentResource;
use App\Jobs\SanitizeUploadedImageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KycDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()->kycDocuments()->latest()->get();

        return response()->apiSuccess(KycDocumentResource::collection($documents));
    }

    /**
     * Stored on the private "local" disk (never public/), filename
     * randomized, mime/size whitelisted by the Form Request — per
     * 05-security-compliance.md.
     */
    public function store(StoreKycDocumentRequest $request): JsonResponse
    {
        $user = $request->user();
        $file = $request->file('file');

        $path = $file->storeAs(
            "kyc/{$user->id}",
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            'local',
        );

        $document = $user->kycDocuments()->create([
            'doc_type' => $request->string('doc_type')->toString(),
            'file_path' => $path,
            'status' => 'pending',
        ]);

        // Re-encodes to strip embedded scripts/EXIF (05-security-
        // compliance.md); a no-op for PDF uploads since GD can't decode
        // them at all — sanitize() just returns null and skips the write.
        SanitizeUploadedImageJob::dispatch('local', $path);

        return response()->apiSuccess(new KycDocumentResource($document), 'Document submitted for review.', [], 201);
    }
}
