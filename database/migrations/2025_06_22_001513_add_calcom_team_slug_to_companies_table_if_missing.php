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
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'calcom_team_slug')) {
                $table->string('calcom_team_slug')->nullable()->after('calcom_api_key');
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
        
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'calcom_team_slug')) {
                $table->dropColumn('calcom_team_slug');
            }
        });
    }
};