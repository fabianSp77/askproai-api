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
        Schema::table('branches', function (Blueprint $table) {
            // Prüfe und füge fehlende Felder hinzu
            if (!Schema::hasColumn('branches', 'website')) {
                $table->string('website')->nullable()->after('notification_email');
            }
            
            if (!Schema::hasColumn('branches', 'country')) {
                $table->string('country')->default('Deutschland')->after('postal_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'website')) {
                $table->dropColumn('website');
            }
            
            if (Schema::hasColumn('branches', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
