<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige le rôle des transporteurs existants pour leur permettre
     * d'accéder au panel transporteur (canAccessPanel).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->whereIn('type', ['transporter', 'transporteur'])
            ->update(['role' => 'transporter']);
    }

    public function down(): void
    {
        // On ne restaure pas les anciennes valeurs (indéterminées)
    }
};
