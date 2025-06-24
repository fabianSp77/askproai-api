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
        // Global feature flags
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(false);
            $table->json('metadata')->nullable(); // For additional config
            $table->string('rollout_percentage')->default('0'); // 0-100
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
            
            $table->index('key');
            $table->index('enabled');
        });
        
        // Company-specific overrides
        Schema::create('feature_flag_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled');
            $table->string('reason')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            
            $table->unique(['feature_key', 'company_id']);
            $table->index('feature_key');
            $table->index('company_id');
            
            $table->foreign('feature_key')->references('key')->on('feature_flags')->onDelete('cascade');
        });
        
        // Feature flag usage log
        Schema::create('feature_flag_usage', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key');
            $table->string('company_id')->nullable();
            $table->string('user_id')->nullable();
            $table->boolean('result'); // Was it enabled or not
            $table->string('evaluation_reason')->nullable(); // global, override, rollout
            $table->timestamps();
            
            $table->index('feature_key');
            $table->index('created_at');
            $table->index(['feature_key', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flag_usage');
        Schema::dropIfExists('feature_flag_overrides');
        Schema::dropIfExists('feature_flags');
    }
};