<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'transporter_id',
        'order_id',
        'status',
        'pickup_date',
        'delivery_date',
        'tracking_number',
        'notes'
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
    ];

    /**
     * Get the transporter that owns the delivery
     */
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    /**
     * Get the order associated with the delivery
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
} 