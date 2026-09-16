<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AmenityMaster;
use App\Models\CityMaster;
use App\Models\LocalityMaster;
use App\Models\PropertyTypeMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public reference-data lookups. Any client building a "post a
 * property" form needs these to populate its dropdowns — doc04 never
 * listed them, but there's no other way to know a valid city_id/
 * locality_id/property_type_id to submit. Cached: this data changes
 * rarely (new cities/amenities are an admin/ops event, not a user one).
 *
 * Caches plain arrays (->toArray()), not raw Eloquent Collections —
 * some PHP environments fail to unserialize cached Collection objects
 * cleanly (surfaces as __PHP_Incomplete_Class_Name in the response),
 * while plain arrays have no class identity to corrupt.
 */
class MasterDataController extends Controller
{
    public function propertyTypes(): JsonResponse
    {
        $types = Cache::remember('master:property-types', now()->addDay(), fn () => PropertyTypeMaster::orderBy('name')->get(['id', 'name'])->toArray());

        return response()->apiSuccess($types);
    }

    public function amenities(): JsonResponse
    {
        $amenities = Cache::remember('master:amenities', now()->addDay(), fn () => AmenityMaster::orderBy('category')->get(['id', 'name', 'icon', 'category'])->toArray());

        return response()->apiSuccess($amenities);
    }

    public function cities(): JsonResponse
    {
        $cities = Cache::remember('master:cities', now()->addDay(), fn () => CityMaster::orderBy('name')->get(['id', 'name', 'state', 'country'])->toArray());

        return response()->apiSuccess($cities);
    }

    public function localities(Request $request): JsonResponse
    {
        $request->validate(['city_id' => ['required', 'integer', 'exists:cities_master,id']]);

        $localities = LocalityMaster::where('city_id', $request->integer('city_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->apiSuccess($localities);
    }
}
