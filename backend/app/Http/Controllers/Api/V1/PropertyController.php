<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPropertiesRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Jobs\DetectDuplicateListingJob;
use App\Models\Property;
use App\Models\PropertyView;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PropertyController extends Controller
{
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Public browse/search. Filters per 04-api-specification.md: city,
     * locality, type, listing_type, min_price, max_price, bedrooms, lat,
     * lng, radius_km, sort, page.
     */
    public function index(SearchPropertiesRequest $request): JsonResponse
    {
        $query = Property::query()
            ->where('status', 'live')
            ->with(['propertyType', 'city', 'locality', 'images' => fn ($q) => $q->where('is_primary', true)]);

        // Typo-tolerant free-text search (Sprint 6) via Scout/Meilisearch —
        // narrows to matching IDs, then every other filter below still
        // applies normally on top of that.
        $query->when($request->filled('q'), function ($q) use ($request) {
            $ids = Property::search($request->string('q')->toString())->keys();
            $q->whereIn('id', $ids);
        });

        $query->when($request->filled('city'), fn ($q) => $q->where('city_id', $request->integer('city')));
        $query->when($request->filled('locality'), fn ($q) => $q->where('locality_id', $request->integer('locality')));
        $query->when($request->filled('type'), fn ($q) => $q->where('property_type_id', $request->integer('type')));
        $query->when($request->filled('listing_type'), fn ($q) => $q->where('listing_type', $request->string('listing_type')));
        $query->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->input('min_price')));
        $query->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->input('max_price')));
        $query->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', $request->integer('bedrooms')));

        $hasRadius = $request->filled('lat') && $request->filled('lng') && $request->filled('radius_km');

        if ($hasRadius) {
            $this->applyBoundingBox($query, (float) $request->input('lat'), (float) $request->input('lng'), (float) $request->input('radius_km'));
        }

        match ($request->string('sort')->toString()) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'featured' => $query->orderByDesc('is_featured')->latest(),
            default => $query->latest(),
        };

        $perPage = $request->integer('per_page') ?: 20;
        $properties = $query->paginate($perPage);

        if ($hasRadius) {
            $this->attachDistancesAndFilter($properties->getCollection(), (float) $request->input('lat'), (float) $request->input('lng'), (float) $request->input('radius_km'));
        }

        return response()->apiSuccess(
            PropertyResource::collection($properties->getCollection()),
            'OK',
            ['page' => $properties->currentPage(), 'per_page' => $properties->perPage(), 'total' => $properties->total()],
        );
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        if ($property->status !== 'live') {
            abort_unless($request->user()?->can('view', $property), 404);
        }

        $property->load(['propertyType', 'city', 'locality', 'images', 'videos', 'amenities', 'owner.sellerProfile', 'owner.agentProfile']);

        $this->recordView($request, $property);

        return response()->apiSuccess(new PropertyResource($property));
    }

    /**
     * Every listing a seller/agent owns, any status — doc08's "my-listings
     * management (edit/pause/mark-sold)".
     */
    public function myListings(Request $request): JsonResponse
    {
        $properties = $request->user()->properties()
            ->with(['propertyType', 'city', 'locality', 'images' => fn ($q) => $q->where('is_primary', true)])
            ->latest()
            ->paginate(20);

        return response()->apiSuccess(PropertyResource::collection($properties));
    }

    public function similar(Property $property): JsonResponse
    {
        $similar = Property::query()
            ->where('status', 'live')
            ->where('id', '!=', $property->id)
            ->where('city_id', $property->city_id)
            ->where('property_type_id', $property->property_type_id)
            ->with(['propertyType', 'city', 'locality', 'images' => fn ($q) => $q->where('is_primary', true)])
            ->limit(6)
            ->get();

        return response()->apiSuccess(PropertyResource::collection($similar));
    }

    public function featured(): JsonResponse
    {
        $featured = Cache::remember('properties:featured', now()->addMinutes(5), fn () => Property::query()
            ->where('status', 'live')
            ->where('is_featured', true)
            ->with(['propertyType', 'city', 'locality', 'images' => fn ($q) => $q->where('is_primary', true)])
            ->latest()
            ->limit(20)
            ->get());

        return response()->apiSuccess(PropertyResource::collection($featured));
    }

    public function store(StorePropertyRequest $request, HtmlSanitizer $sanitizer): JsonResponse
    {
        $property = new Property($request->safe()->except(['is_draft', 'amenity_ids']));
        $property->description = $sanitizer->clean($property->description);
        $property->owner_id = $request->user()->id;
        $property->status = $request->boolean('is_draft') ? 'draft' : 'pending_review';
        $property->save();

        if ($request->filled('amenity_ids')) {
            $property->amenities()->sync($request->input('amenity_ids'));
        }

        DetectDuplicateListingJob::dispatch($property->id);

        return response()->apiSuccess(new PropertyResource($property), 'Property submitted.', [], 201);
    }

    public function update(UpdatePropertyRequest $request, Property $property, HtmlSanitizer $sanitizer): JsonResponse
    {
        $property->fill($request->safe()->except(['amenity_ids']));

        if ($request->has('description')) {
            $property->description = $sanitizer->clean($property->description);
        }

        $property->save();

        if ($request->has('amenity_ids')) {
            $property->amenities()->sync($request->input('amenity_ids'));
        }

        return response()->apiSuccess(new PropertyResource($property->fresh(['propertyType', 'city', 'locality', 'amenities'])), 'Property updated.');
    }

    /**
     * Owner-facing status changes doc08 calls "pause/mark-sold" — never
     * lets the client set moderation states (live/rejected) directly.
     */
    public function updateStatus(Request $request, Property $property): JsonResponse
    {
        abort_unless($request->user()->can('update', $property), 403);

        $request->validate(['status' => ['required', 'string', 'in:paused,sold,live']]);
        $newStatus = $request->string('status')->toString();

        abort_if($newStatus === 'live' && $property->status !== 'paused', 422, 'Only a paused listing can be reactivated.');

        $property->update(['status' => $newStatus]);

        return response()->apiSuccess(new PropertyResource($property), 'Status updated.');
    }

    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);

        $property->delete();

        return response()->apiSuccess(null, 'Property deleted.');
    }

    public function report(Request $request, Property $property): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $property->reports()->create([
            'reported_by' => $request->user()->id,
            'reason' => $request->string('reason')->toString(),
            'status' => 'open',
        ]);

        return response()->apiSuccess(null, 'Report submitted.', [], 201);
    }

    protected function afterCreate(Property $property): void
    {
        DetectDuplicateListingJob::dispatch($property->id);
    }

    private function applyBoundingBox($query, float $lat, float $lng, float $radiusKm): void
    {
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(cos(deg2rad($lat)), 0.01));

        $query->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
    }

    /**
     * The bounding box above is a superset of the true circle (cheap,
     * portable across MySQL/SQLite) — this trims it to the exact radius
     * and attaches distance_km for display/sorting. Precision here is a
     * Sprint 2 stopgap; Sprint 6 wires real geo search via Meilisearch.
     */
    private function attachDistancesAndFilter($collection, float $lat, float $lng, float $radiusKm): void
    {
        $filtered = $collection->filter(function (Property $property) use ($lat, $lng, $radiusKm) {
            $distance = $this->haversineKm($lat, $lng, (float) $property->latitude, (float) $property->longitude);
            $property->distance_km = $distance;

            return $distance <= $radiusKm;
        })->sortBy('distance_km')->values();

        $collection->splice(0, $collection->count(), $filtered->all());
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function recordView(Request $request, Property $property): void
    {
        $viewerId = $request->user()?->id;
        $ipHash = hash('sha256', $request->ip());

        $recentDuplicate = PropertyView::query()
            ->where('property_id', $property->id)
            ->when($viewerId, fn ($q) => $q->where('viewer_id', $viewerId), fn ($q) => $q->where('ip_hash', $ipHash))
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        PropertyView::create([
            'property_id' => $property->id,
            'viewer_id' => $viewerId,
            'ip_hash' => $ipHash,
            'viewed_at' => now(),
        ]);

        $property->increment('views_count');
    }
}
