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
        
        if (!Schema::hasTable('integrations')) {
            return;
        }

        Schema::table('integrations', function (Blueprint $table) {
            // Check and add missing columns
            if (!Schema::hasColumn('integrations', 'provider')) {
                $table->string('provider')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'integrable_type')) {
                $table->nullableMorphs('integrable');
            }
            if (!Schema::hasColumn('integrations', 'provider_version')) {
                $table->string('provider_version')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'api_key')) {
                $table->text('api_key')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'api_secret')) {
                $table->text('api_secret')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'access_token')) {
                $table->text('access_token')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'refresh_token')) {
                $table->text('refresh_token')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'webhook_url')) {
                $table->text('webhook_url')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'webhook_secret')) {
                $table->text('webhook_secret')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'field_mappings')) {
                $table->json('field_mappings')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'sync_settings')) {
                $table->json('sync_settings')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'environment')) {
                $table->string('environment')->default('production');
            }
            if (!Schema::hasColumn('integrations', 'health_status')) {
                $table->enum('health_status', ['healthy', 'degraded', 'unhealthy', 'unknown'])
                    ->default('unknown');
            }
            if (!Schema::hasColumn('integrations', 'health_score')) {
                $table->integer('health_score')->default(0);
            }
            if (!Schema::hasColumn('integrations', 'last_error')) {
                $table->text('last_error')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'error_count')) {
                $table->integer('error_count')->default(0);
            }
            if (!Schema::hasColumn('integrations', 'success_count')) {
                $table->integer('success_count')->default(0);
            }
            if (!Schema::hasColumn('integrations', 'last_success_at')) {
                $table->timestamp('last_success_at')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'last_error_at')) {
                $table->timestamp('last_error_at')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'next_sync_at')) {
                $table->timestamp('next_sync_at')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'sync_interval_minutes')) {
                $table->integer('sync_interval_minutes')->default(60);
            }
            if (!Schema::hasColumn('integrations', 'auto_sync')) {
                $table->boolean('auto_sync')->default(false);
            }
            if (!Schema::hasColumn('integrations', 'api_calls_count')) {
                $table->bigInteger('api_calls_count')->default(0);
            }
            if (!Schema::hasColumn('integrations', 'api_calls_limit')) {
                $table->bigInteger('api_calls_limit')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'records_synced')) {
                $table->bigInteger('records_synced')->default(0);
            }
            if (!Schema::hasColumn('integrations', 'usage_stats')) {
                $table->json('usage_stats')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'is_visible')) {
                $table->boolean('is_visible')->default(true);
            }
            if (!Schema::hasColumn('integrations', 'requires_auth')) {
                $table->boolean('requires_auth')->default(true);
            }
            if (!Schema::hasColumn('integrations', 'permissions')) {
                $table->json('permissions')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'external_id')) {
                $table->string('external_id')->nullable()->index();
            }
            if (!Schema::hasColumn('integrations', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('integrations', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'provider_version',
                'description',
                'api_key',
                'api_secret',
                'access_token',
                'refresh_token',
                'webhook_url',
                'webhook_secret',
                'field_mappings',
                'sync_settings',
                'environment',
                'health_status',
                'health_score',
                'last_error',
                'error_count',
                'success_count',
                'last_success_at',
                'last_error_at',
                'next_sync_at',
                'sync_interval_minutes',
                'auto_sync',
                'api_calls_count',
                'api_calls_limit',
                'records_synced',
                'usage_stats',
                'is_visible',
                'requires_auth',
                'permissions',
                'external_id',
                'metadata',
                'created_by',
                'updated_by',
            ]);
            $table->dropMorphs('integrable');
        });
    }
};