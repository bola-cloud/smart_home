<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'ar_title',
        'en_title',
        'ar_description',
        'en_description',
        'ar_small_description',
        'en_small_description',
        'image',
        'price',
        'quantity',
    ];

    public function checkoutItems()
    {
        return $this->hasMany(CheckoutItem::class);
    }
    
}
