<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LocalityMaster;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LocalityController extends Controller
{
    /**
     * Avg price/sqft + a naive month-bucket trend from live listings —
     * doc03 has no historical price-snapshot table, so "trend" here is
     * computed from current live data grouped by listing month, not a
     * true time series. Cached because this aggregates across every
     * live property in the locality on every request otherwise.
     */
    public function insights(LocalityMaster $locality): JsonResponse
    {
        $data = Cache::remember("locality:{$locality->id}:insights", now()->addHours(6), function () use ($locality) {
            $liveProperties = Property::where('locality_id', $locality->id)
                ->where('status', 'live')
                ->whereNotNull('area_sqft')
                ->where('area_sqft', '>', 0)
                ->get();

            $currentAvg = $liveProperties->isNotEmpty()
                ? round($liveProperties->avg(fn (Property $p) => $p->price / $p->area_sqft), 2)
                : (float) $locality->avg_price_sqft;

            // ->toArray() — this whole return value gets cached, and this
            // environment fails to unserialize a cached Collection object
            // cleanly (see PropertyController::featured()'s docblock).
            $trend = $liveProperties
                ->groupBy(fn (Property $p) => $p->created_at->format('Y-m'))
                ->map(fn ($group) => round($group->avg(fn (Property $p) => $p->price / $p->area_sqft), 2))
                ->sortKeys()
                ->toArray();

            return [
                'locality' => $locality->name,
                'avg_price_sqft' => $currentAvg,
                'sample_size' => $liveProperties->count(),
                'trend_by_month' => $trend,
            ];
        });

        return response()->apiSuccess($data);
    }
}
