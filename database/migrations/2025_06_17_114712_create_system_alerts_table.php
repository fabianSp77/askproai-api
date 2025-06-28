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
        $this->createTableIfNotExists('system_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service', 50);
            $table->string('type', 50);
            $table->text('message');
            $this->addJsonColumn($table, 'context', true);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('acknowledged')->default(false);
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            
            $table->index(['service', 'type']);
            $table->index('severity');
            $table->index('created_at');
            $table->index(['acknowledged', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('system_alerts');
    }
};
