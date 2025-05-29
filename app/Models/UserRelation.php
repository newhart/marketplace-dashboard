<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRelation extends Model
{
    protected $fillable = [
        'customer_id',
        'seller_id',
        'transporter_id',
        'status',
        'notes'
    ];

    /**
     * Get the customer in this relation
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the seller in this relation
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Get the transporter in this relation
     */
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
} 