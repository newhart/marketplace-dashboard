<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transporter extends User
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->type = self::TYPE_TRANSPORTER;
        });
    }

    /**
     * Get the transporter's deliveries
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Get the transporter's relations with customers
     */
    public function customerRelations(): HasMany
    {
        return $this->hasMany(UserRelation::class, 'transporter_id');
    }

    /**
     * Get the customers related to this transporter
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'user_relations', 'transporter_id', 'customer_id')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the sellers related to this transporter
     */
    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(Seller::class, 'user_relations', 'transporter_id', 'seller_id')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get transporter specific data
     */
    public function getTransporterData(): array
    {
        return [
            'company_name' => $this->company_name,
            'vehicle_type' => $this->vehicle_type,
            'license_number' => $this->license_number,
            'insurance_number' => $this->insurance_number,
        ];
    }
} 