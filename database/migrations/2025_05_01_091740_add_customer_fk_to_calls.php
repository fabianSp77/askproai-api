<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            if (! Schema::hasColumn('calls','customer_id')) {
                $t->foreignId('customer_id')->nullable()
                  ->constrained()->cascadeOnDelete();
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
        
        Schema::table('calls', function (Blueprint $t) {
            if (Schema::hasColumn('calls','customer_id')) {
                $t->dropForeign(['customer_id']);
                $t->dropColumn('customer_id');
            }
        });
    }
};
