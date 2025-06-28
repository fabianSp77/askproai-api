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
        Schema::table('calls', function (Blueprint $table) {
            // Add extracted fields if they don't exist
            if (!Schema::hasColumn('calls', 'extracted_name')) {
                $table->string('extracted_name')->nullable()->after('analysis');
            }
            if (!Schema::hasColumn('calls', 'extracted_email')) {
                $table->string('extracted_email')->nullable()->after('extracted_name');
            }
            if (!Schema::hasColumn('calls', 'extracted_date')) {
                $table->string('extracted_date')->nullable()->after('extracted_email');
            }
            if (!Schema::hasColumn('calls', 'extracted_time')) {
                $table->string('extracted_time')->nullable()->after('extracted_date');
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
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['extracted_name', 'extracted_email', 'extracted_date', 'extracted_time']);
        });
    }
};