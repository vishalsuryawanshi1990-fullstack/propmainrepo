<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'icon', 'category'])]
class AmenityMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'amenities_master';
}
