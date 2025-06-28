<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->uuid('home_branch_id')->nullable();

            $table->foreign('home_branch_id')
                  ->references('id')->on('branches')
                  ->nullOnDelete();
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
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['home_branch_id']);
            $table->dropColumn('home_branch_id');
        });
    }
};
