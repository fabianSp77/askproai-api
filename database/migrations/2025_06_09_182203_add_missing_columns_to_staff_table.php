<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('staff', 'external_calendar_id')) {
                $table->string('external_calendar_id')->nullable();
            }
            if (!Schema::hasColumn('staff', 'calendar_provider')) {
                $table->string('calendar_provider')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['notes', 'external_calendar_id', 'calendar_provider']);
        });
    }
};
