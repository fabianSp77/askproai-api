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
            $table->string('service', 50);
            $table->string('operation', 100)->nullable();
            $table->boolean('success')->default(true);
            $table->decimal('duration_ms', 10, 2)->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $this->addJsonColumn($table, 'metadata', true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('service');
            $table->index('success');
            $table->index('created_at');
            $table->index(['service', 'created_at']);
            $table->index(['tenant_id', 'service']);
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