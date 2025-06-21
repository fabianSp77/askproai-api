<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // API Call Logs for tracking performance
        if (!Schema::hasTable('api_call_logs')) {
            $this->createTableIfNotExists('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50); // calcom, retell, etc.
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('status_code');
            $table->decimal('duration_ms', 10, 2);
            $table->string('correlation_id', 36)->nullable();
            $table->text('error_message')->nullable();
            $this->addJsonColumn($table, 'metadata', true);
            $table->timestamps();
            
            $table->index(['service', 'created_at']);
            $table->index('correlation_id');
            $table->index(['status_code', 'created_at']);
            });
        }
        
        // Metric snapshots for historical tracking
        if (!Schema::hasTable('metric_snapshots')) {
            Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->string('metric_type', 50); // operational, financial, conversion
            $table->string('period', 20); // hour, day, week, month
            $this->addJsonColumn($table, 'metrics', true);
            $table->timestamp('snapshot_at');
            $table->timestamps();
            
            $table->index(['company_id', 'branch_id', 'metric_type', 'snapshot_at']);
            });
        }
        
        // Anomaly logs
        if (!Schema::hasTable('anomaly_logs')) {
            Schema::create('anomaly_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->string('anomaly_type', 50);
            $table->string('severity', 20); // warning, critical
            $table->text('message');
            $table->decimal('current_value', 10, 2);
            $table->decimal('expected_min', 10, 2)->nullable();
            $table->decimal('expected_max', 10, 2)->nullable();
            $this->addJsonColumn($table, 'context', true);
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('acknowledged_by')->nullable();
            $table->foreign('acknowledged_by')->references('id')->on('users');
            $table->timestamps();
            
            $table->index(['company_id', 'severity', 'created_at']);
            $table->index(['acknowledged', 'created_at']);
            });
        }
        
        // Dashboard widget configurations per user
        if (!Schema::hasTable('dashboard_widget_settings')) {
            Schema::create('dashboard_widget_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('dashboard_type', 50); // executive, operational, analytics
            $this->addJsonColumn($table, 'widget_order', true);
            $this->addJsonColumn($table, 'widget_visibility', true);
            $this->addJsonColumn($table, 'preferences', true);
            $table->timestamps();
            
            $table->unique(['user_id', 'dashboard_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widget_settings');
        Schema::dropIfExists('anomaly_logs');
        Schema::dropIfExists('metric_snapshots');
        $this->dropTableIfExists('api_call_logs');
    }
};