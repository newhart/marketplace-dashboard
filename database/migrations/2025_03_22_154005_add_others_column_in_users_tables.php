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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable(); 
            $table->string('phone_prefix'); 
            $table->string('phone_number'); 
            $table->string('address_one');
            $table->string('address_tow')->nullable();
            $table->string('city_one');
            $table->string('city_tow')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_name');
            $table->dropColumn('phone_prefix');
            $table->dropColumn('phone_number');
            $table->dropColumn('address_one');
            $table->dropColumn('address_tow');
            $table->dropColumn('city_one');
            $table->dropColumn('city_tow');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('postal_code');
        });
    }
};
