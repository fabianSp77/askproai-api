<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'sentiment_score')) {
                $table->float('sentiment_score')->nullable()->after('cost_cents');
            }
            if (!Schema::hasColumn('calls', 'wait_time_sec')) {
                $table->integer('wait_time_sec')->nullable()->after('duration_sec');
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
            $table->dropColumn(['sentiment_score', 'wait_time_sec']);
        });
    }
};