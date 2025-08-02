<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // System metrics table
        Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();
            $table->decimal('value', 10, 2);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->index();
            
            $table->index(['type', 'created_at']);
        });
        
        // Error logs table
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('severity', 20)->index();
            $table->text('message');
            $table->string('type')->nullable()->index();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->json('context')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('request_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->index();
            
            $table->index(['severity', 'created_at']);
            $table->index(['type', 'created_at']);
        });
        
        // API endpoint metrics
        Schema::create('api_endpoint_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->index();
            $table->string('method', 10);
            $table->integer('status_code');
            $table->decimal('response_time', 10, 2);
            $table->integer('memory_usage')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();
            
            $table->index(['endpoint', 'created_at']);
            $table->index(['endpoint', 'status_code', 'created_at']);
        });
        
        // Health check results
        Schema::create('health_check_results', function (Blueprint $table) {
            $table->id();
            $table->string('component', 50)->index();
            $table->string('status', 20);
            $table->decimal('response_time', 10, 2)->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->index();
            
            $table->index(['component', 'status', 'created_at']);
        });
        
        // Alert configurations
        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('metric');
            $table->string('condition', 10);
            $table->decimal('threshold', 10, 2);
            $table->integer('duration')->default(5);
            $table->json('actions');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();
            
            $table->index(['enabled', 'metric']);
        });
        
        // Alert history
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alert_id')->index();
            $table->string('status', 20);
            $table->decimal('value', 10, 2);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            
            $table->foreign('alert_id')->references('id')->on('monitoring_alerts')->onDelete('cascade');
            $table->index(['alert_id', 'triggered_at']);
        });
        
        // Performance snapshots
        Schema::create('performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('cpu_usage', 5, 2);
            $table->decimal('memory_usage', 5, 2);
            $table->bigInteger('memory_used_bytes');
            $table->decimal('disk_usage', 5, 2);
            $table->integer('active_connections');
            $table->integer('queue_size');
            $table->integer('cache_hit_rate');
            $table->json('additional_metrics')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_snapshots');
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('monitoring_alerts');
        Schema::dropIfExists('health_check_results');
        Schema::dropIfExists('api_endpoint_metrics');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('system_metrics');
    }
};