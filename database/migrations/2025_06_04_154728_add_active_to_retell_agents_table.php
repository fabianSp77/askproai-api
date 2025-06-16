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
        Schema::table('retell_agents', function (Blueprint $table) {
            // Füge active-Spalte hinzu, ohne auf voice_settings zu verweisen
            if (!Schema::hasColumn('retell_agents', 'active')) {
                $table->boolean('active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retell_agents', function (Blueprint $table) {
            if (Schema::hasColumn('retell_agents', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
