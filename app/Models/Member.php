<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Member extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'user_id', 
        'name', 
        'email', 
        'phone_number', 
        'password', 
        'devices',
        'reset_code_expires_at',
        'reset_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'devices' => 'array',  // Devices stored as JSON
    ];

    // Member belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
