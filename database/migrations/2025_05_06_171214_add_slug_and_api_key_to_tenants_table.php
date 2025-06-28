<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            // slug (falls noch nicht da)
            if (!Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->unique();
            }

            // api_key  (wird von Seeder benötigt)
            if (!Schema::hasColumn('tenants', 'api_key')) {
                $table->string('api_key')->nullable();
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
        
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slug', 'api_key']);
        });
    }
};
