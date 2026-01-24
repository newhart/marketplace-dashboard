<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transporter_price_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_per_km', 10, 2)->default(0)->comment('Prix en XPF par kilomètre');
            $table->decimal('minimum_amount', 10, 2)->nullable()->comment('Montant minimum de livraison en XPF');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporter_price_settings');
    }
};
