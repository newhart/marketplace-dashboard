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
            // Add firstname and lastname columns if they don't exist
            if (!Schema::hasColumn('users', 'firstname')) {
                $table->string('firstname')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'lastname')) {
                $table->string('lastname')->nullable();
            }
            
            // Add postal_address column if it doesn't exist
            if (!Schema::hasColumn('users', 'postal_address')) {
                $table->string('postal_address')->nullable();
            }
            
            // Add geographic_address column if it doesn't exist
            if (!Schema::hasColumn('users', 'geographic_address')) {
                $table->string('geographic_address')->nullable();
            }
            
            // Note: phone is already covered by phone_number in the existing schema
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop the columns if they exist
            if (Schema::hasColumn('users', 'firstname')) {
                $table->dropColumn('firstname');
            }
            
            if (Schema::hasColumn('users', 'lastname')) {
                $table->dropColumn('lastname');
            }
            
            if (Schema::hasColumn('users', 'postal_address')) {
                $table->dropColumn('postal_address');
            }
            
            if (Schema::hasColumn('users', 'geographic_address')) {
                $table->dropColumn('geographic_address');
            }
        });
    }
};
