<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterPriceSetting extends Model
{
    protected $fillable = ['user_id', 'price_per_km', 'minimum_amount'];

    protected $casts = [
        'price_per_km' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
