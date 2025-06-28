<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'privacy_consent_at')) {
                $table->timestamp('privacy_consent_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'marketing_consent_at')) {
                $table->timestamp('marketing_consent_at')->nullable()->after('privacy_consent_at');
            }
            if (!Schema::hasColumn('customers', 'deletion_requested_at')) {
                $table->timestamp('deletion_requested_at')->nullable()->after('marketing_consent_at');
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
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['privacy_consent_at', 'marketing_consent_at', 'deletion_requested_at']);
        });
    }
};