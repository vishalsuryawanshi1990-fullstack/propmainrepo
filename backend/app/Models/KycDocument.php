<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'doc_type', 'file_path', 'status', 'verified_by', 'verified_at', 'rejection_reason'])]
class KycDocument extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * file_path is encrypted at rest per 05-security-compliance.md — a
     * DB dump/leak shouldn't reveal the storage layout of KYC documents.
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'file_path' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
