<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycDocumentRequest;
use App\Http\Resources\KycDocumentResource;
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

        // TODO(doc05): re-encode image uploads server-side to strip embedded
        // scripts/EXIF before this ships past Sprint 1 — not done here yet.
        $document = $user->kycDocuments()->create([
            'doc_type' => $request->string('doc_type')->toString(),
            'file_path' => $path,
            'status' => 'pending',
        ]);

        return response()->apiSuccess(new KycDocumentResource($document), 'Document submitted for review.', [], 201);
    }
}
