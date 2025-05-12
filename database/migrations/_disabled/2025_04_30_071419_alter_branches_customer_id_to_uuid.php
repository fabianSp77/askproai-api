<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // FK vorher entfernen, falls er noch existiert
            if (Schema::hasColumn('branches', 'customer_id')) {
                try {
                    $table->dropForeign(['customer_id']);
                } catch (\Throwable $e) {
                    // FK war schon weg – ignorieren
                }
            }

            // Spalte auf CHAR(36) umstellen (UUID) und nullable lassen
            $table->char('customer_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // zurück auf BIGINT UNSIGNED
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });
    }
};
