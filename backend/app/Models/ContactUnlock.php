<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unlocker_user_id', 'property_id', 'credits_spent', 'unlocked_at'])]
class ContactUnlock extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocker_user_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
