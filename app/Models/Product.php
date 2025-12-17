<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotions(): BelongsToMany
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

    /**
     * Get similar products based on category
     */
    public function getSimilarProducts(int $limit = 6)
    {
        return self::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->with(['category', 'images'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get related products based on multiple criteria (advanced similarity)
     */
    public function getRelatedProducts(int $limit = 6)
    {
        // Produits de la même catégorie avec un score de similarité
        $sameCategory = self::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->with(['category', 'images']);

        // Si pas assez de produits dans la même catégorie, inclure d'autres catégories
        $sameCategoryCount = $sameCategory->count();

        if ($sameCategoryCount < $limit) {
            $remaining = $limit - $sameCategoryCount;
            $otherProducts = self::where('category_id', '!=', $this->category_id)
                ->with(['category', 'images'])
                ->inRandomOrder()
                ->limit($remaining)
                ->get();

            return $sameCategory->get()->merge($otherProducts);
        }

        return $sameCategory->inRandomOrder()->limit($limit)->get();
    }

    /**
     * Get recommended products based on price range
     */
    public function getRecommendedByPriceRange(int $limit = 6)
    {
        $priceVariation = $this->price * 0.3; // 30% de variation de prix
        $minPrice = $this->price - $priceVariation;
        $maxPrice = $this->price + $priceVariation;

        return self::whereBetween('price', [$minPrice, $maxPrice])
            ->where('id', '!=', $this->id)
            ->with(['category', 'images'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
