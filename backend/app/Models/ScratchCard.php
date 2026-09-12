<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'triggered_by', 'reward_id', 'is_scratched', 'scratched_at', 'expires_at'])]
class ScratchCard extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_scratched' => 'boolean',
            'scratched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(ScratchRewardMaster::class, 'reward_id');
    }
}
