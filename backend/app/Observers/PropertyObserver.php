<?php

namespace App\Observers;

use App\Models\Property;
use Illuminate\Support\Facades\Cache;

/**
 * Sprint 6 caching invalidation — featured listings and locality
 * insights are cached with a short-ish TTL (see PropertyController and
 * LocalityController), but a write should never leave a stale cache
 * sitting around until the TTL expires on its own. Also keeps the
 * denormalized city_name/locality_name columns in sync — see Property's
 * toSearchableArray() docblock for why they're denormalized at all.
 */
class PropertyObserver
{
    public function saving(Property $property): void
    {
        if ($property->isDirty('city_id')) {
            $property->city_name = $property->city?->name;
        }

        if ($property->isDirty('locality_id') || $property->isDirty('locality_text')) {
            $property->locality_name = $property->locality_id ? $property->locality?->name : $property->locality_text;
        }
    }

    public function created(Property $property): void
    {
        $this->invalidate($property);
    }

    public function updated(Property $property): void
    {
        $this->invalidate($property);
    }

    public function deleted(Property $property): void
    {
        $this->invalidate($property);
    }

    public function restored(Property $property): void
    {
        $this->invalidate($property);
    }

    private function invalidate(Property $property): void
    {
        Cache::forget('properties:featured');
        Cache::forget("locality:{$property->locality_id}:insights");
    }
}
