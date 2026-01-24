<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user type constants
     */
    const TYPE_CUSTOMER = 'customer';
    const TYPE_SELLER = 'seller';
    const TYPE_TRANSPORTER = 'transporter';
    const TYPE_MERCHANT = 'merchant';

    /**
     * Check if user is a customer
     */
    public function isCustomer(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    /**
     * Check if user is a seller
     */
    public function isSeller(): bool
    {
        return $this->type === self::TYPE_SELLER;
    }

    /**
     * Check if user is a transporter
     */
    public function isTransporter(): bool
    {
        return $this->type === self::TYPE_TRANSPORTER;
    }

    /**
     * Get seller specific data
     */
    public function getSellerData(): array
    {
        if (!$this->isSeller()) {
            return [];
        }

        return [
            'company_name' => $this->company_name,
            'business_registration' => $this->business_registration,
            'tax_number' => $this->tax_number,
        ];
    }

    /**
     * Get transporter specific data
     */
    public function getTransporterData(): array
    {
        if (!$this->isTransporter()) {
            return [];
        }

        return [
            'company_name' => $this->company_name,
            'vehicle_type' => $this->vehicle_type,
            'license_number' => $this->license_number,
            'insurance_number' => $this->insurance_number,
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the merchant profile associated with the user.
     */
    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class);
    }

    /**
     * Get the transporter price settings (prix au km).
     */
    public function transporterPriceSetting(): HasOne
    {
        return $this->hasOne(TransporterPriceSetting::class);
    }

    /**
     * Check if user is a merchant
     */
    public function isMerchant(): bool
    {
        return $this->type === self::TYPE_MERCHANT;
    }

    /**
     * Get the notification routing information for the Firebase channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|null
     */
    public function routeNotificationForFirebase($notification)
    {
        return $this->fcm_token;
    }

    /**
     * Détermine si l'utilisateur peut accéder à un panel Filament.
     * - Super admin (rôle 'admin') : accès à tous les panels
     * - Admin : panel admin
     * - Marchant : type 'merchant' ou 'seller', ou rôle 'merchant'
     * - Transporteur : type 'transporter' ou 'transporteur', ou rôle 'transporter' ou 'transporteur'
     * Les comptes désactivés (is_active=false) ne peuvent accéder à aucun panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Comptes désactivés (null = actif par défaut pour compatibilité)
        if (($this->is_active ?? true) === false) {
            return false;
        }

        $role = strtolower((string) ($this->role ?? ''));
        $type = strtolower((string) ($this->type ?? ''));

        // Super admin : accès à tous les panels
        if ($role === 'admin') {
            return true;
        }

        return match ($panel->getId()) {
            'admin' => $role === 'admin',
            'marchantPanel' => in_array($type, [self::TYPE_MERCHANT, self::TYPE_SELLER], true) || $role === 'merchant',
            'transporteur' => in_array($type, ['transporter', 'transporteur'], true)
                || in_array($role, ['transporter', 'transporteur'], true),
            default => false,
        };
    }
}
