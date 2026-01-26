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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'transporter_id')) {
                $table->foreignId('transporter_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'transporter_id')) {
                $table->dropForeign(['transporter_id']);
                $table->dropColumn('transporter_id');
            }
        });
    }
};
