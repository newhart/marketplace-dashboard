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
            // Add transporter-specific fields if they don't exist
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('users', 'siret')) {
                $table->string('siret', 14)->nullable()->after('company_name');
            }
            
            if (!Schema::hasColumn('users', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable()->after('siret');
            }
            
            if (!Schema::hasColumn('users', 'license_number')) {
                $table->string('license_number')->nullable()->after('vehicle_type');
            }
            
            if (!Schema::hasColumn('users', 'insurance_number')) {
                $table->string('insurance_number')->nullable()->after('license_number');
            }
            
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('insurance_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('users', 'company_name')) {
                $table->dropColumn('company_name');
            }
            
            if (Schema::hasColumn('users', 'siret')) {
                $table->dropColumn('siret');
            }
            
            if (Schema::hasColumn('users', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }
            
            if (Schema::hasColumn('users', 'license_number')) {
                $table->dropColumn('license_number');
            }
            
            if (Schema::hasColumn('users', 'insurance_number')) {
                $table->dropColumn('insurance_number');
            }
            
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
