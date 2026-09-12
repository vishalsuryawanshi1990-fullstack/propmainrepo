<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reward_type', 'value', 'probability_weight', 'is_active'])]
class ScratchRewardMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'probability_weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
