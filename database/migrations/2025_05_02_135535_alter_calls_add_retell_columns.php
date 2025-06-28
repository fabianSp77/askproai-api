<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            if (!Schema::hasColumn('calls', 'retell_call_id')) {
                $t->string('retell_call_id')->unique();
            }
            if (!Schema::hasColumn('calls', 'from_number')) {
                $t->string('from_number')->nullable();
            }
            if (!Schema::hasColumn('calls', 'to_number')) {
                $t->string('to_number')->nullable();
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
            $t->dropColumn(['retell_call_id', 'from_number', 'to_number']);
        });
    }
};
