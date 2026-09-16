<?php

namespace App\Models;

use App\Observers\PropertyObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

#[Fillable([
    'owner_id', 'agent_id', 'title', 'description', 'property_type_id', 'listing_type',
    'price', 'price_negotiable', 'area_sqft', 'bedrooms', 'bathrooms', 'floor_no',
    'total_floors', 'furnishing_status', 'city_id', 'locality_id', 'locality_text', 'city_name', 'locality_name', 'address',
    'latitude', 'longitude', 'rera_registration_no', 'status', 'is_featured',
    'address_price_hash', 'primary_image_hash', 'duplicate_of_property_id', 'is_flagged_duplicate',
])]
#[ObservedBy(PropertyObserver::class)]
class Property extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    /**
     * Sprint 6: typo-tolerant free-text search via Meilisearch. Only
     * live listings are indexed/searchable — draft/pending/rejected
     * properties must stay invisible to public search regardless of
     * what a raw Meilisearch query could otherwise match.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'live';
    }

    /**
     * city_name/locality_name are denormalized copies (kept in sync by
     * PropertyObserver) rather than a join — Scout's "database" engine
     * (used locally/in tests) runs plain column LIKE queries and can't
     * join to cities_master/localities_master the way Meilisearch's
     * index can in production.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'address' => $this->address,
            'city_name' => $this->city_name,
            'locality_name' => $this->locality_name,
        ];
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_negotiable' => 'boolean',
            'area_sqft' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'is_flagged_duplicate' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyTypeMaster::class, 'property_type_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(CityMaster::class, 'city_id');
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(LocalityMaster::class, 'locality_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(PropertyVideo::class)->orderBy('sort_order');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(AmenityMaster::class, 'property_amenities', 'property_id', 'amenity_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReportedListing::class);
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function contactUnlocks(): HasMany
    {
        return $this->hasMany(ContactUnlock::class);
    }
}
