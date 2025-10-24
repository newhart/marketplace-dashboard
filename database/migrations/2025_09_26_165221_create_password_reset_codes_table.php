<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code', 6); // Code à 6 chiffres
            $table->timestamp('expires_at'); // Expiration du code
            $table->boolean('is_used')->default(false); // Si le code a été utilisé
            $table->timestamp('used_at')->nullable(); // Quand le code a été utilisé
            $table->string('ip_address')->nullable(); // IP de la demande
            $table->integer('attempts')->default(0); // Nombre de tentatives
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index(['email', 'code']);
            $table->index(['email', 'is_used']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
