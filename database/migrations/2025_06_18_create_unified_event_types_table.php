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
        if (!Schema::hasTable('unified_event_types')) {
            $this->createTableIfNotExists('unified_event_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->char('branch_id', 36)->nullable()->index();
                $table->string('provider')->default('calcom');
                $table->string('external_id')->index();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->integer('duration_minutes')->default(30);
                $table->decimal('price', 10, 2)->nullable();
                $this->addJsonColumn($table, 'provider_data', true);
                $this->addJsonColumn($table, 'conflict_data', true);
                $table->boolean('is_active')->default(true);
                $table->string('assignment_status')->default('unassigned');
                $table->string('import_status')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                
                // Indexes
                $table->index(['company_id', 'is_active']);
                $table->index(['assignment_status', 'company_id']);
                $table->index(['import_status', 'company_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('unified_event_types');
    }
};