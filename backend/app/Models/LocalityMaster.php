<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['city_id', 'name', 'lat', 'long', 'avg_price_sqft'])]
class LocalityMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'localities_master';

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'long' => 'decimal:7',
            'avg_price_sqft' => 'decimal:2',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(CityMaster::class, 'city_id');
    }
}
