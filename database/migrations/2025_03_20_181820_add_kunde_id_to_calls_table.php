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
        Schema::table('calls', function (Blueprint $table) {
            // Füge kunde_id als Fremdschlüssel hinzu, wenn noch nicht vorhanden
            if (! Schema::hasColumn('calls', 'kunde_id')) {
                $table->foreignId('kunde_id')->nullable()->after('raw')
                    ->constrained('kunden')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (Schema::hasColumn('calls', 'kunde_id')) {
                $table->dropForeign(['kunde_id']);
                $table->dropColumn('kunde_id');
            }
        });
    }
};
