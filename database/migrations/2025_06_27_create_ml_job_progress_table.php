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
        $this->createTableIfNotExists('ml_job_progress', function (Blueprint $table) {
            $table->id();
            $table->string('job_id');
            $table->string('job_type'); // 'training', 'analysis'
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->string('current_step')->nullable();
            $table->text('message')->nullable();
            $this->addJsonColumn($table, 'metadata');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes - handle SQLite compatibility
            if (!$this->isSQLite()) {
                $table->unique('job_id');
                $table->index(['job_type', 'status']);
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('ml_job_progress');
    }
};