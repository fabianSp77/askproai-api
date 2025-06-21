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
        if (!Schema::hasTable('validation_results')) {
            $this->createTableIfNotExists('validation_results', function (Blueprint $table) {
                $table->id();
                $table->string('validation_type')->index();
                $table->string('entity_type')->index();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('status'); // passed, failed, warning
                $table->string('severity')->default('info'); // critical, high, medium, low, info
                $table->string('category')->nullable();
                $table->text('message');
                $this->addJsonColumn($table, 'details', true);
                $this->addJsonColumn($table, 'context', true);
                $table->timestamp('validated_at')->useCurrent();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();
                
                // Indexes
                $table->index(['validation_type', 'status']);
                $table->index(['entity_type', 'entity_id']);
                $table->index(['severity', 'created_at']);
                $table->index(['company_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('validation_results');
    }
};