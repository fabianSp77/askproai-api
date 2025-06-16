<?php // database/migrations/..._add_tenant_id_to_calls_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('calls', function (Blueprint $table) {
            // Füge tenant_id hinzu, initial nullable wegen bestehender Daten
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->after('id') // Positionieren nach der ID
                  ->constrained('tenants') // Fremdschlüssel zu tenants.id
                  ->onDelete('cascade'); // Oder 'set null'. Strategie aus Phase 0!
        });
    }

    public function down(): void {
        Schema::table('calls', function (Blueprint $table) {
            // Prüfe, ob die Spalte existiert, bevor Constraint gelöscht wird (optional, robuster)
            if (Schema::hasColumn('calls', 'tenant_id')) {
                 // Prüfe, ob der Fremdschlüssel existiert (Name ist oft tablename_columname_foreign)
                 // Der genaue Name kann variieren, ggf. vorher mit SHOW CREATE TABLE calls prüfen.
                 // $foreignKeys = collect(DB::select("SHOW CREATE TABLE calls"))->first()->{'Create Table'};
                 // if (str_contains($foreignKeys, 'calls_tenant_id_foreign')) { ... }
                 try { $table->dropForeign(['tenant_id']); } catch (\Exception $e) { /* Ignorieren, wenn nicht vorhanden */ }
                 $table->dropColumn('tenant_id');
            }
        });
    }
};
