<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('calcom_team_slug')
                  ->nullable()
                  
                  ->index()
                  ->comment('Der Team-Slug des Mandanten bei Cal.com');
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
        
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('calcom_team_slug');
        });
    }
};
