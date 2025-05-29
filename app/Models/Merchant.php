<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'manager_lastname',
        'manager_firstname',
        'mobile_phone',
        'landline_phone',
        'business_address',
        'business_city',
        'business_postal_code',
        'business_type',
        'business_description',
        'approval_status', // pending, approved, rejected
        'rejection_reason',
    ];

    /**
     * Get the user that owns the merchant account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
