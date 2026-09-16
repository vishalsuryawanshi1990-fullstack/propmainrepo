<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'state', 'country'])]
class CityMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cities_master';

    public function localities(): HasMany
    {
        return $this->hasMany(LocalityMaster::class, 'city_id');
    }
}
