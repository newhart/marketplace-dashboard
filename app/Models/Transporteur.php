<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transporteur extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'siret',
        'vehicle_type',
        'license_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 