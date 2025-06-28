<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // nur anlegen, wenn Spalte noch fehlt
            if (!Schema::hasColumn('calls', 'call_status')) {
                $table->string('call_status')->nullable();
            }
            if (!Schema::hasColumn('calls', 'call_successful')) {
                $table->boolean('call_successful')->nullable();
            }
            if (!Schema::hasColumn('calls', 'duration_sec')) {
                $table->unsignedInteger('duration_sec')->nullable();
            }
            if (!Schema::hasColumn('calls', 'analysis')) {
                $this->addJsonColumn($table, 'analysis', true);
            }
            if (!Schema::hasColumn('calls', 'transcript')) {
                $table->longText('transcript')->nullable();
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
            $table->dropColumn([
                'call_status',
                'call_successful',
                'duration_sec',
                'analysis',
                'transcript',
            ]);
        });
    }
};
