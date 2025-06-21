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
            $this->addColumnIfNotExists('webhook_logs', 'is_duplicate', function (Blueprint $table) {
                $table->boolean('is_duplicate')->default(false)->after('retry_count');
            });
            
            // Add index for duplicate detection
            $this->addIndexIfNotExists('webhook_logs', ['is_duplicate'], 'webhook_logs_is_duplicate_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $this->dropColumnIfExists('webhook_logs', 'is_duplicate');
        });
    }
};