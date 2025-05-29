<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Seller extends Model
{
    const TYPE_CUSTOMER = 'customer';
    const TYPE_SELLER = 'seller';
    const TYPE_TRANSPORTER = 'transporter';

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->type = self::TYPE_SELLER;
        });
    }

    protected $fillable = [
        'user_id',
        'company_name',
        'business_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the seller's products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the seller's categories
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the seller's orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the seller's relations with customers
     */
    public function customerRelations(): HasMany
    {
        return $this->hasMany(UserRelation::class, 'seller_id');
    }

    /**
     * Get the customers related to this seller
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'user_relations', 'seller_id', 'customer_id')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the transporters related to this seller
     */
    public function transporters(): BelongsToMany
    {
        return $this->belongsToMany(Transporter::class, 'user_relations', 'seller_id', 'transporter_id')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get seller specific data
     */
    public function getSellerData(): array
    {
        return [
            'company_name' => $this->company_name,
            'business_registration' => $this->business_registration,
            'tax_number' => $this->tax_number,
        ];
    }
} 