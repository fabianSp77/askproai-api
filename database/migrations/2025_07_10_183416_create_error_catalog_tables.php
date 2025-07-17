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
        // Main error catalog table
        Schema::create('error_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('error_code')->unique()->index(); // e.g., RETELL_001
            $table->string('category', 50)->index(); // AUTH, API, INTEGRATION, DB, QUEUE, UI
            $table->string('service', 50)->nullable()->index(); // retell, calcom, stripe, etc.
            $table->string('title');
            $table->text('description');
            $table->text('symptoms')->nullable(); // What user sees
            $table->text('stack_pattern')->nullable(); // Stack trace pattern for auto-detection
            $table->text('root_causes'); // JSON array of possible causes
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_detectable')->default(false);
            $table->integer('occurrence_count')->default(0);
            $table->timestamp('last_occurred_at')->nullable();
            $table->decimal('avg_resolution_time', 8, 2)->nullable(); // in minutes
            $table->timestamps();
            
            $table->index(['category', 'severity']);
            
            // Fulltext index nur für MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText(['title', 'description', 'symptoms']);
            }
        });

        // Solutions for each error
        Schema::create('error_solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('error_catalog_id')->constrained('error_catalog')->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->string('type', 50); // manual, script, command, config
            $table->string('title');
            $table->text('description');
            $table->text('steps'); // JSON array of steps
            $table->text('code_snippet')->nullable();
            $table->boolean('is_automated')->default(false);
            $table->string('automation_script')->nullable(); // Path to automation script
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable(); // Calculated percentage
            $table->timestamps();
            
            $table->index(['error_catalog_id', 'order']);
        });

        // Prevention tips
        Schema::create('error_prevention_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('error_catalog_id')->constrained('error_catalog')->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->text('tip');
            $table->string('category', 50); // configuration, monitoring, testing, deployment
            $table->timestamps();
            
            $table->index(['error_catalog_id', 'order']);
        });

        // Related errors (for suggesting similar issues)
        Schema::create('error_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('error_id')->constrained('error_catalog')->cascadeOnDelete();
            $table->foreignId('related_error_id')->constrained('error_catalog')->cascadeOnDelete();
            $table->string('relationship_type', 50); // similar, causes, caused_by, related
            $table->integer('relevance_score')->default(50); // 0-100
            $table->timestamps();
            
            $table->unique(['error_id', 'related_error_id']);
            $table->index(['error_id', 'relevance_score']);
        });

        // Tags for better categorization
        Schema::create('error_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->string('color', 7)->default('#6B7280'); // Hex color
            $table->timestamps();
        });

        // Many-to-many relationship for error tags
        Schema::create('error_tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('error_catalog_id')->constrained('error_catalog')->cascadeOnDelete();
            $table->foreignId('error_tag_id')->constrained('error_tags')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['error_catalog_id', 'error_tag_id']);
        });

        // Track error occurrences for analytics
        Schema::create('error_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('error_catalog_id')->constrained('error_catalog')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('environment', 20)->default('production'); // production, staging, local
            $table->text('context')->nullable(); // JSON with additional context
            $table->text('stack_trace')->nullable();
            $table->string('request_url')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('was_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->integer('resolution_time')->nullable(); // in seconds
            $table->foreignId('solution_id')->nullable()->constrained('error_solutions')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['error_catalog_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index('environment');
        });

        // User feedback on solutions
        Schema::create('error_solution_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solution_id')->constrained('error_solutions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('was_helpful');
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->index(['solution_id', 'was_helpful']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_solution_feedback');
        Schema::dropIfExists('error_occurrences');
        Schema::dropIfExists('error_tag_assignments');
        Schema::dropIfExists('error_tags');
        Schema::dropIfExists('error_relationships');
        Schema::dropIfExists('error_prevention_tips');
        Schema::dropIfExists('error_solutions');
        Schema::dropIfExists('error_catalog');
    }
};