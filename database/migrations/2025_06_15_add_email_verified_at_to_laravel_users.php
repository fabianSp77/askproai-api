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
        // Check if the table exists first
        if (!Schema::hasTable('laravel_users')) {
            return;
        }
        
        if (!Schema::hasColumn('laravel_users', 'email_verified_at')) {
            Schema::table('laravel_users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
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
        
        // Check if the table exists first
        if (!Schema::hasTable('laravel_users')) {
            return;
        }
        
        if (Schema::hasColumn('laravel_users', 'email_verified_at')) {
            Schema::table('laravel_users', function (Blueprint $table) {
                $table->dropColumn('email_verified_at');
            });
        }
    }
};