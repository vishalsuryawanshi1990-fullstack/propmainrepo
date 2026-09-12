<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'ad_network', 'request_token', 'ssv_signature', 'ssv_verified', 'credited'])]
class VideoAdEvent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ssv_verified' => 'boolean',
            'credited' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
