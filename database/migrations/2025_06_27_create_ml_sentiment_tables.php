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
        // ML Call Predictions table
        Schema::create('ml_call_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->string('model_version', 50);
            
            // Overall scores
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('-1.0 to 1.0');
            $table->decimal('satisfaction_score', 3, 2)->nullable()->comment('0.0 to 1.0');
            $table->decimal('goal_achievement_score', 3, 2)->nullable()->comment('0.0 to 1.0');
            
            // Sentence-level analysis
            $table->json('sentence_sentiments')->nullable()->comment('[{text, start_time, end_time, sentiment, score}]');
            
            // Feature importance
            $table->json('feature_contributions')->nullable();
            
            // Metadata
            $table->decimal('prediction_confidence', 3, 2)->nullable();
            $table->integer('processing_time_ms')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['call_id', 'sentiment_score']);
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
        });
        
        // Agent Performance Metrics table
        Schema::create('agent_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id', 255);
            $table->unsignedBigInteger('company_id');
            $table->date('date');
            
            // Aggregate metrics
            $table->integer('total_calls')->default(0);
            $table->decimal('avg_sentiment_score', 3, 2)->nullable();
            $table->decimal('avg_satisfaction_score', 3, 2)->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable()->comment('percentage');
            $table->integer('avg_call_duration_sec')->nullable();
            
            // Breakdown by outcome
            $table->integer('positive_calls')->default(0);
            $table->integer('neutral_calls')->default(0);
            $table->integer('negative_calls')->default(0);
            $table->integer('converted_calls')->default(0);
            
            // Time-based patterns
            $table->json('hourly_metrics')->nullable()->comment('{hour: {calls, sentiment, conversion}}');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['agent_id', 'date']);
            $table->index(['date', 'avg_sentiment_score']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
        
        // Add ML model table for storing trained models
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 50); // sentiment, satisfaction, etc.
            $table->string('version', 20);
            $table->string('file_path', 500);
            $table->json('training_metrics')->nullable();
            $table->json('feature_importance')->nullable();
            $table->integer('training_samples')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_models');
        Schema::dropIfExists('agent_performance_metrics');
        Schema::dropIfExists('ml_call_predictions');
    }
};