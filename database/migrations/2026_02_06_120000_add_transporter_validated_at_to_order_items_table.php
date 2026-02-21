<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Date à laquelle le transporteur a validé (pris en charge) cet item.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('transporter_validated_at')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('transporter_validated_at');
        });
    }
};
