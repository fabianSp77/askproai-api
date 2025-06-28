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
        if (!Schema::hasColumn('companies', 'trial_ends_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable()->after('is_active');
            });
        }
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
        
        if (Schema::hasColumn('companies', 'trial_ends_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('trial_ends_at');
            });
        }
    }
};