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
        if (Schema::hasTable('branch_service')) {
            Schema::table('branch_service', function (Blueprint $table) {
                // Prüfen ob die Spalten schon existieren und nur hinzufügen wenn nicht
                if (!Schema::hasColumn('branch_service', 'price')) {
                    $table->decimal('price', 10, 2)->nullable()->after('service_id');
                }
                if (!Schema::hasColumn('branch_service', 'duration')) {
                    $table->integer('duration')->nullable()->after('price');
                }
                if (!Schema::hasColumn('branch_service', 'active')) {
                    $table->boolean('active')->default(true)->after('duration');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_service', function (Blueprint $table) {
            $table->dropColumn(['price', 'duration', 'active']);
        });
    }
};
