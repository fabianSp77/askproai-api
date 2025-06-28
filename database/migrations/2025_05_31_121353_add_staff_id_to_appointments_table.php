<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'staff_id')) {
                $table->uuid('staff_id')
                    ->nullable()
                    ;
                
                $table->index('staff_id');
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
        
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasIndex('appointments', 'idx_staff_id')) {
                $table->dropIndex('idx_staff_id');
            }
            if (Schema::hasColumn('appointments', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });
    }
};
