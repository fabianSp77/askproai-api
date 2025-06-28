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
        // Add version field to appointments for optimistic locking
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(0);
            $table->index('version');
        });
        
        // Add version field to calls for optimistic locking
        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(0);
            $table->index('version');
        });
        
        // Add lock_expires_at to appointments for pessimistic locking during booking
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('lock_expires_at')->nullable();
            $table->string('lock_token')->nullable();
            $table->index(['lock_expires_at', 'lock_token']);
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
        
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('version');
            $table->dropColumn('lock_expires_at');
            $table->dropColumn('lock_token');
        });
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
