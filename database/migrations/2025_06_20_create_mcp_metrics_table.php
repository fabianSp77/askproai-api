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
        $this->createTableIfNotExists('mcp_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50)->index();
            $table->boolean('success')->default(true)->index();
            $table->decimal('duration_ms', 10, 2);
            $table->integer('tenant_id')->nullable()->index();
            $table->string('operation', 100)->nullable();
            $this->addJsonColumn($table, 'metadata', true);
            $table->timestamp('created_at')->index();
            
            // Composite indexes for performance queries
            $table->index(['service', 'created_at']);
            $table->index(['service', 'success', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('mcp_metrics');
    }
};