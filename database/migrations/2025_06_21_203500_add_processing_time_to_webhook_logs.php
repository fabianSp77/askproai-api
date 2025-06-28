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
        Schema::table('webhook_logs', function (Blueprint $table) {
            // Add missing columns for webhook monitoring
            $this->addColumnIfNotExists('webhook_logs', 'processing_time_ms', function (Blueprint $table) {
                $table->integer('processing_time_ms')->nullable()->after('status');
            });
            
            $this->addColumnIfNotExists('webhook_logs', 'provider', function (Blueprint $table) {
                $table->string('provider', 50)->nullable()->after('webhook_type');
            });
            
            $this->addColumnIfNotExists('webhook_logs', 'retry_count', function (Blueprint $table) {
                $table->integer('retry_count')->default(0)->after('error_message');
            });
            
            // Add indexes for performance
            $this->addIndexIfNotExists('webhook_logs', ['created_at'], 'webhook_logs_created_at_index');
            $this->addIndexIfNotExists('webhook_logs', ['provider'], 'webhook_logs_provider_index');
            $this->addIndexIfNotExists('webhook_logs', ['status'], 'webhook_logs_status_index');
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
        
        Schema::table('webhook_logs', function (Blueprint $table) {
            $this->dropColumnIfExists('webhook_logs', 'processing_time_ms');
            $this->dropColumnIfExists('webhook_logs', 'provider');
            $this->dropColumnIfExists('webhook_logs', 'retry_count');
        });
    }
};