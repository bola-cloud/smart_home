<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Member extends Model
{
    use HasApiTokens, HasFactory;

    protected $casts = [
        'devices' => 'array',  // Cast devices column to array (JSON)
    ];

    protected $fillable = [
        'owner_id',
        'member_id',
        'project_id',
        'devices',
        'full_access',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
