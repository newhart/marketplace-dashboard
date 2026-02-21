<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'validated_at' => 'datetime',
        'transporter_validated_at' => 'datetime',
    ];

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Vérifier si l'item est validé par le commerçant
     */
    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    /**
     * Vérifier si l'item a été validé (pris en charge) par le transporteur
     */
    public function isTransporterValidated(): bool
    {
        return $this->transporter_validated_at !== null;
    }
}
