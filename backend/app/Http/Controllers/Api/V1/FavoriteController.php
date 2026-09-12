<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->whereHas('favoritedBy', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['propertyType', 'city', 'locality', 'images' => fn ($q) => $q->where('is_primary', true)])
            ->paginate(20);

        return response()->apiSuccess(PropertyResource::collection($properties));
    }

    public function store(Request $request, Property $property): JsonResponse
    {
        $request->user()->favorites()->firstOrCreate(['property_id' => $property->id]);

        return response()->apiSuccess(null, 'Added to favorites.', [], 201);
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        $request->user()->favorites()->where('property_id', $property->id)->delete();

        return response()->apiSuccess(null, 'Removed from favorites.');
    }
}
