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
        Schema::table('phone_numbers', function (Blueprint $table) {
            if (!Schema::hasColumn('phone_numbers', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('active');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(false)->after('is_primary');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')->default(false)->after('sms_enabled');
            }
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
        
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['is_primary', 'sms_enabled', 'whatsapp_enabled']);
        });
    }
};