<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory ; 
    
    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews() : HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy() : BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function orderItems() : HasMany 
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotions () : BelongsToMany
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function getDiscountedPriceAttribute()
    {
        $discount = $this->promotions->max('discount'); 
        return $this->price * (1 - $discount / 100);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function firstActiveImage()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->oldestOfMany()
            ->where('is_main', true);
    }

    public function getFirstImageAttribute()
    {
        return $this->images()->where('is_main', true)->first();
    }
    
}
