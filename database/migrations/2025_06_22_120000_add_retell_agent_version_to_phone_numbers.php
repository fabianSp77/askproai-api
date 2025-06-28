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
        if (!Schema::hasColumn('phone_numbers', 'retell_agent_version')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->string('retell_agent_version')->nullable()->after('retell_agent_id');
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
        
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn('retell_agent_version');
        });
    }
};