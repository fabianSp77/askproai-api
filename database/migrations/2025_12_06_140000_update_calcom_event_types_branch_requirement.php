<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Skip this migration if running tests with SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Check if the table exists
        if (!Schema::hasTable('calcom_event_types')) {
            return;
        }

        // Erst sicherstellen dass alle NULL branch_ids eine Default-Filiale bekommen
        $nullBranchEventTypes = DB::table('calcom_event_types')
            ->whereNull('branch_id')
            ->get();
            
        foreach ($nullBranchEventTypes as $eventType) {
            // Nimm die erste Filiale des Unternehmens
            $mainBranch = DB::table('branches')
                ->where('company_id', $eventType->company_id)
                ->orderBy('created_at')
                ->first();
            
            if ($mainBranch) {
                DB::table('calcom_event_types')
                    ->where('id', $eventType->id)
                    ->update(['branch_id' => $mainBranch->id]);
            } else {
                // Wenn keine Branch gefunden, lösche den Event-Type
                DB::table('calcom_event_types')
                    ->where('id', $eventType->id)
                    ->delete();
                    
                Log::warning('Deleted orphaned event type without branch', ['event_type_id' => $eventType->id]);
            }
        }
        
        // Jetzt können wir branch_id auf NOT NULL setzen
        // Verwende raw statement für bessere Kontrolle
        DB::statement('ALTER TABLE calcom_event_types MODIFY branch_id CHAR(36) NOT NULL');
        
        // Handle unique constraints in separate schema call to avoid issues
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Prüfe ob der alte unique constraint existiert
            $indexes = DB::select("SHOW INDEXES FROM calcom_event_types WHERE Key_name = 'unique_calcom_event'");
            if (!empty($indexes)) {
                try {
                    // Versuche den alten constraint zu entfernen
                    DB::statement('ALTER TABLE calcom_event_types DROP INDEX unique_calcom_event');
                } catch (\Exception $e) {
                    // Log aber ignoriere den Fehler falls constraint in Verwendung ist
                    Log::warning('Could not drop unique_calcom_event index', ['error' => $e->getMessage()]);
                }
            }
            
            // Prüfe ob der neue constraint bereits existiert
            $newIndexes = DB::select("SHOW INDEXES FROM calcom_event_types WHERE Key_name = 'unique_branch_event_name'");
            if (empty($newIndexes)) {
                // Neuer unique constraint auf branch_id + name
                $table->unique(['branch_id', 'name'], 'unique_branch_event_name');
            }
        });
    }

    public function down(): void
    {
        // Skip this migration if running tests with SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Check if the table exists
        if (!Schema::hasTable('calcom_event_types')) {
            return;
        }

        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->change();
            
            // Entferne neuen constraint
            $table->dropUnique('unique_branch_event_name');
            
            // Stelle alten constraint wieder her
            $table->unique(['company_id', 'calcom_event_type_id'], 'unique_calcom_event');
        });
    }
};