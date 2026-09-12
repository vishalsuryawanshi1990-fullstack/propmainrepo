<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectKycDocumentRequest;
use App\Http\Resources\KycDocumentResource;
use App\Models\KycDocument;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycReviewController extends Controller
{
    public function pending(): JsonResponse
    {
        $documents = KycDocument::where('status', 'pending')->latest()->paginate(20);

        return response()->apiSuccess(
            KycDocumentResource::collection($documents),
            'OK',
            ['page' => $documents->currentPage(), 'per_page' => $documents->perPage(), 'total' => $documents->total()],
        );
    }

    public function verify(Request $request, KycDocument $kycDocument, NotificationService $notifications): JsonResponse
    {
        $before = $kycDocument->toArray();

        $kycDocument->forceFill([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ])->save();

        AuditLogger::log('kyc.verify', $kycDocument, $before, $kycDocument->toArray());

        $notifications->notify($kycDocument->user, 'kyc.verified', 'Document verified', 'Your '.$kycDocument->doc_type.' has been verified.');

        return response()->apiSuccess(new KycDocumentResource($kycDocument), 'KYC document verified.');
    }

    public function reject(RejectKycDocumentRequest $request, KycDocument $kycDocument, NotificationService $notifications): JsonResponse
    {
        $before = $kycDocument->toArray();

        $kycDocument->forceFill([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => $request->string('rejection_reason')->toString(),
        ])->save();

        AuditLogger::log('kyc.reject', $kycDocument, $before, $kycDocument->toArray());

        $notifications->notify($kycDocument->user, 'kyc.rejected', 'Document rejected', $kycDocument->rejection_reason);

        return response()->apiSuccess(new KycDocumentResource($kycDocument), 'KYC document rejected.');
    }

    /**
     * Streams the private file to a reviewer. Reached only via the
     * temporary signed URL issued in KycDocumentResource.
     */
    public function download(Request $request, KycDocument $kycDocument): mixed
    {
        abort_unless($request->hasValidSignature(), 403);

        return Storage::disk('local')->response($kycDocument->file_path);
    }
}
