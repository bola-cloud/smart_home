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
        'quantity',
    ];

    public function checkoutItems()
    {
        return $this->hasMany(CheckoutItem::class);
    }

    public function prices()
    {
        return $this->hasMany(CountryPrice::class);
    }    

    // Dynamic price based on country
    public function getPriceForCountry($country)
    {
        return $this->prices()->where('country', $country)->first()->price ?? null;
    }
}
