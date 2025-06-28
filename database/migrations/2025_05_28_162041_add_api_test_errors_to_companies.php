<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $this->addJsonColumn($table, 'api_test_errors', true);
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
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('api_test_errors');
        });
    }
};
