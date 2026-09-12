<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyModerationController extends Controller
{
    public function pending(): JsonResponse
    {
        $properties = Property::where('status', 'pending_review')
            ->with(['propertyType', 'city', 'locality', 'owner', 'images'])
            ->latest()
            ->paginate(20);

        return response()->apiSuccess(
            PropertyResource::collection($properties),
            'OK',
            ['page' => $properties->currentPage(), 'per_page' => $properties->perPage(), 'total' => $properties->total()],
        );
    }

    public function approve(Property $property, NotificationService $notifications): JsonResponse
    {
        $before = $property->toArray();
        $property->update(['status' => 'live']);
        AuditLogger::log('property.approve', $property, $before, $property->fresh()->toArray());

        $notifications->notify($property->owner, 'property.approved', 'Listing approved', "\"{$property->title}\" is now live.");

        return response()->apiSuccess(new PropertyResource($property), 'Property approved.');
    }

    public function reject(Request $request, Property $property, NotificationService $notifications): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $before = $property->toArray();

        $property->update(['status' => 'rejected']);
        AuditLogger::log('property.reject', $property, $before, $property->fresh()->toArray());

        $notifications->notify(
            $property->owner,
            'property.rejected',
            'Listing rejected',
            "\"{$property->title}\" was rejected: {$request->string('reason')}",
        );

        return response()->apiSuccess(new PropertyResource($property), 'Property rejected.');
    }
}
