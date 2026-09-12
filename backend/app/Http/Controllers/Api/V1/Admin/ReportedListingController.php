<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportedListing;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportedListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = ReportedListing::with(['property', 'reporter'])
            ->where('status', $request->input('status', 'open'))
            ->latest()
            ->paginate(20);

        return response()->apiPaginated($reports, fn (ReportedListing $r) => [
            'id' => $r->id,
            'property_id' => $r->property_id,
            'property_title' => $r->property?->title,
            'reported_by' => $r->reported_by,
            'reason' => $r->reason,
            'status' => $r->status,
            'created_at' => $r->created_at,
        ]);
    }

    public function resolve(Request $request, ReportedListing $reportedListing): JsonResponse
    {
        $before = $reportedListing->toArray();

        $reportedListing->update(['status' => 'resolved', 'resolved_by' => $request->user()->id]);
        AuditLogger::log('report.resolve', $reportedListing, $before, $reportedListing->fresh()->toArray());

        return response()->apiSuccess(null, 'Report resolved.');
    }
}
