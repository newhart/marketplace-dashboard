<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PromotionalBanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'start_date',
        'end_date',
        'is_active',
        'display_order',
        'product_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the product associated with the banner
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only active banners
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get current promotions (within date range or no dates set)
     */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query->where(function ($q) use ($today) {
            $q->where(function ($subQ) use ($today) {
                // Both dates are null (always active)
                $subQ->whereNull('start_date')
                    ->whereNull('end_date');
            })
                ->orWhere(function ($subQ) use ($today) {
                    // Start date is set, end date is null, and today >= start_date
                    $subQ->whereNotNull('start_date')
                        ->whereNull('end_date')
                        ->whereDate('start_date', '<=', $today);
                })
                ->orWhere(function ($subQ) use ($today) {
                    // Both dates are set, and today is between them
                    $subQ->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today);
                })
                ->orWhere(function ($subQ) use ($today) {
                    // Only end date is set, and today <= end_date
                    $subQ->whereNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('end_date', '>=', $today);
                });
        });
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image);
    }
}
