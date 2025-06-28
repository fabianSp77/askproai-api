<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'duration_sec')) {
                $table->unsignedInteger('duration_sec')->nullable();
            }
            if (!Schema::hasColumn('calls', 'tmp_call_id')) {
                $table->uuid('tmp_call_id')->nullable();
            }
        });
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['duration_sec', 'tmp_call_id']);
        });
    }
};
