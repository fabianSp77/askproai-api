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
        $this->createTableIfNotExists('circuit_breaker_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('status');
            $table->string('state');
            $table->integer('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('service');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('circuit_breaker_metrics');
    }
};