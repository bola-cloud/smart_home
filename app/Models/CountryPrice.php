<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryPrice extends Model
{
    use HasFactory;
    protected $fillable = [
        'country',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }  
}
