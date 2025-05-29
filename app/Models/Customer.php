<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends User
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->type = self::TYPE_CUSTOMER;
        });
    }

    /**
     * Get the customer's orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the customer's favorites
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /**
     * Get the customer's reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the customer's relations with sellers
     */
    public function sellerRelations(): HasMany
    {
        return $this->hasMany(UserRelation::class, 'customer_id');
    }

    /**
     * Get the sellers related to this customer
     */
    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(Seller::class, 'user_relations', 'customer_id', 'seller_id')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the transporters related to this customer
     */
    public function transporters(): BelongsToMany
    {
        return $this->belongsToMany(Transporter::class, 'user_relations', 'customer_id', 'transporter_id')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }
} 