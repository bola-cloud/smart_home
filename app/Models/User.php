<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'category',
        'reset_code',
        'reset_code_expires_at',
        'notification',
        'country',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class,'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class,'user_id');
    }

    public function memberProjects()
    {
        return $this->belongsToMany(Project::class, 'members', 'member_id', 'project_id')
                    ->withPivot('devices')
                    ->withTimestamps();
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class,'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class,'user_id');
    }

    public function checkout(): HasMany
    {
        return $this->hasMany(Checkout::class,'user_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];
}
