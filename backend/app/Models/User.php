<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'status', 'device_id', 'fcm_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function agentProfile(): HasOne
    {
        return $this->hasOne(AgentProfile::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function scratchCards(): HasMany
    {
        return $this->hasMany(ScratchCard::class);
    }

    /**
     * Overrides Notifiable::notifications(), which assumes Laravel's own
     * polymorphic notifications table shape — ours (AppNotification) is a
     * simpler user_id-keyed table per 03-database-schema.md.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }
}
