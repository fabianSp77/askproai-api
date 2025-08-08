<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Strategic performance indexes for optimizing specific query patterns.
     * 
     * Analysis shows core indexes already exist, but these provide micro-optimizations
     * for specific dashboard and reporting queries.
     */
    public function up(): void
    {
        // Check if indexes already exist before creating them
        $this->createIndexIfNotExists('calls', 'idx_calls_phone_company_time', ['from_number', 'company_id', 'created_at']);
        $this->createIndexIfNotExists('appointments', 'idx_appointments_service_status_time', ['service_id', 'status', 'starts_at']);
        $this->createIndexIfNotExists('customers', 'idx_customers_phone_company_lookup', ['phone', 'company_id']);
        $this->createIndexIfNotExists('calls', 'idx_calls_conversion_tracking', ['company_id', 'appointment_id', 'created_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_phone_company_time');
            $table->dropIndex('idx_calls_conversion_tracking');
        });
        
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_service_status_time');
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_phone_company_lookup');
        });
    }
    
    /**
     * Create index only if it doesn't already exist
     */
    private function createIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        // Check if index exists
        $exists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        
        if (empty($exists)) {
            echo "📊 Creating index {$indexName} on {$table}...\n";
            Schema::table($table, function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
            echo "✅ Index {$indexName} created successfully\n";
        } else {
            echo "⚠️  Index {$indexName} already exists on {$table}, skipping...\n";
        }
    }
};
