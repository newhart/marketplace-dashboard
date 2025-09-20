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
            // Add phone column if it doesn't exist
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            
            // Add firstname column if it doesn't exist
            if (!Schema::hasColumn('users', 'firstname')) {
                $table->string('firstname')->nullable();
            }
            
            // Add lastname column if it doesn't exist
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
            
            // Add birth_date column if it doesn't exist (may already exist from another migration)
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
            
            // Add type column if it doesn't exist (may already exist from another migration)
            if (!Schema::hasColumn('users', 'type')) {
                $table->string('type')->default('customer');
            }
            
            // Add is_approved column if it doesn't exist (may already exist from another migration)
            if (!Schema::hasColumn('users', 'is_approved')) {
                $table->boolean('is_approved')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop columns only if they exist
            $columnsToRemove = ['phone', 'firstname', 'lastname', 'postal_address', 'geographic_address'];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
