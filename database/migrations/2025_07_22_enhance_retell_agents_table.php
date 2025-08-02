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
        // The table might exist from the old migration or might not
        if (!Schema::hasTable('retell_agents')) {
            // Create the full table if it doesn't exist
            Schema::create('retell_agents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->string('retell_agent_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type')->default('general'); // general, sales, support, appointments, custom
                $table->string('language')->default('de'); // Language code
                $table->json('capabilities')->nullable(); // What the agent can do
                $table->json('voice_settings')->nullable(); // Voice configuration
                $table->json('prompt_settings')->nullable(); // Custom prompts
                $table->json('integration_settings')->nullable(); // Integration configs
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('priority')->default(0); // For selection logic
                
                // Performance metrics
                $table->integer('total_calls')->default(0);
                $table->integer('successful_calls')->default(0);
                $table->float('average_duration')->default(0);
                $table->float('satisfaction_score')->nullable();
                
                // Testing
                $table->boolean('is_test_agent')->default(false);
                $table->json('test_config')->nullable();
                
                $table->timestamps();
                
                // Indexes
                $table->index(['company_id', 'is_active']);
                $table->index(['company_id', 'type', 'is_active']);
            });
        } else {
            // Add missing columns to existing table
            Schema::table('retell_agents', function (Blueprint $table) {
                // Check and add each column if it doesn't exist
                if (!Schema::hasColumn('retell_agents', 'company_id')) {
                    $table->foreignId('company_id')->after('id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('retell_agents', 'retell_agent_id')) {
                    $table->string('retell_agent_id')->after('company_id')->index();
                }
                if (!Schema::hasColumn('retell_agents', 'description')) {
                    $table->text('description')->after('name')->nullable();
                }
                if (!Schema::hasColumn('retell_agents', 'type')) {
                    $table->string('type')->after('description')->default('general');
                }
                if (!Schema::hasColumn('retell_agents', 'language')) {
                    $table->string('language')->after('type')->default('de');
                }
                if (!Schema::hasColumn('retell_agents', 'capabilities')) {
                    $table->json('capabilities')->after('language')->nullable();
                }
                if (!Schema::hasColumn('retell_agents', 'voice_settings')) {
                    $table->json('voice_settings')->after('capabilities')->nullable();
                }
                if (!Schema::hasColumn('retell_agents', 'prompt_settings')) {
                    $table->json('prompt_settings')->after('voice_settings')->nullable();
                }
                if (!Schema::hasColumn('retell_agents', 'integration_settings')) {
                    $table->json('integration_settings')->after('prompt_settings')->nullable();
                }
                if (!Schema::hasColumn('retell_agents', 'is_active')) {
                    $table->boolean('is_active')->after('integration_settings')->default(true);
                }
                if (!Schema::hasColumn('retell_agents', 'is_default')) {
                    $table->boolean('is_default')->after('is_active')->default(false);
                }
                if (!Schema::hasColumn('retell_agents', 'priority')) {
                    $table->integer('priority')->after('is_default')->default(0);
                }
                if (!Schema::hasColumn('retell_agents', 'total_calls')) {
                    $table->integer('total_calls')->after('priority')->default(0);
                }
                if (!Schema::hasColumn('retell_agents', 'successful_calls')) {
                    $table->integer('successful_calls')->after('total_calls')->default(0);
                }
                if (!Schema::hasColumn('retell_agents', 'average_duration')) {
                    $table->float('average_duration')->after('successful_calls')->default(0);
                }
                if (!Schema::hasColumn('retell_agents', 'satisfaction_score')) {
                    $table->float('satisfaction_score')->after('average_duration')->nullable();
                }
                if (!Schema::hasColumn('retell_agents', 'is_test_agent')) {
                    $table->boolean('is_test_agent')->after('satisfaction_score')->default(false);
                }
                if (!Schema::hasColumn('retell_agents', 'test_config')) {
                    $table->json('test_config')->after('is_test_agent')->nullable();
                }
                
                // Add indexes if they don't exist
                $existingIndexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('retell_agents');
                if (!array_key_exists('retell_agents_company_id_is_active_index', $existingIndexes)) {
                    $table->index(['company_id', 'is_active']);
                }
                if (!array_key_exists('retell_agents_company_id_type_is_active_index', $existingIndexes)) {
                    $table->index(['company_id', 'type', 'is_active']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the entire table - this will handle both cases
        Schema::dropIfExists('retell_agents');
    }
};