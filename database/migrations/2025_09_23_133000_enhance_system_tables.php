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
        // Enhance system_settings table
        
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('system_settings', 'category')) {
                $table->string('category')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'priority')) {
                $table->integer('priority')->default(0);
            }
            if (!Schema::hasColumn('system_settings', 'cache_ttl')) {
                $table->integer('cache_ttl')->default(3600);
            }
            if (!Schema::hasColumn('system_settings', 'requires_restart')) {
                $table->boolean('requires_restart')->default(false);
            }
            if (!Schema::hasColumn('system_settings', 'last_changed_at')) {
                $table->timestamp('last_changed_at')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'change_count')) {
                $table->integer('change_count')->default(0);
            }
            if (!Schema::hasColumn('system_settings', 'default_value')) {
                $table->text('default_value')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'min_value')) {
                $table->string('min_value')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'max_value')) {
                $table->string('max_value')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'validation_message')) {
                $table->text('validation_message')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'help_text')) {
                $table->text('help_text')->nullable();
            }
            if (!Schema::hasColumn('system_settings', 'is_readonly')) {
                $table->boolean('is_readonly')->default(false);
            }
            if (!Schema::hasColumn('system_settings', 'is_system')) {
                $table->boolean('is_system')->default(false);
            }
            if (!Schema::hasColumn('system_settings', 'is_visible')) {
                $table->boolean('is_visible')->default(true);
            }
            if (!Schema::hasColumn('system_settings', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            // Add indices
            $table->index(['group', 'category']);
            $table->index('priority');
            $table->index('is_visible');
        });

        // Enhance activity_log table
        
        if (!Schema::hasTable('activity_log')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_log', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'type')) {
                $table->string('type')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'severity')) {
                $table->string('severity')->default('info');
            }
            if (!Schema::hasColumn('activity_log', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'method')) {
                $table->string('method', 10)->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'url')) {
                $table->text('url')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'status_code')) {
                $table->integer('status_code')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'response_time')) {
                $table->integer('response_time')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'session_id')) {
                $table->string('session_id')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'old_values')) {
                $table->json('old_values')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'new_values')) {
                $table->json('new_values')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'changes')) {
                $table->json('changes')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'context')) {
                $table->json('context')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'is_read')) {
                $table->boolean('is_read')->default(false);
            }
            if (!Schema::hasColumn('activity_log', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'is_important')) {
                $table->boolean('is_important')->default(false);
            }
            if (!Schema::hasColumn('activity_log', 'is_archived')) {
                $table->boolean('is_archived')->default(false);
            }
            if (!Schema::hasColumn('activity_log', 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable();
            }
            if (!Schema::hasColumn('activity_log', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }

            // Add indices
            $table->index('user_id');
            $table->index('type');
            $table->index('severity');
            $table->index('event');
            $table->index('ip_address');
            $table->index('status_code');
            $table->index('is_read');
            $table->index('is_important');
            $table->index('is_archived');
            $table->index(['created_at', 'severity']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropIndex(['group', 'category']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['is_visible']);

            $columns = [
                'category', 'priority', 'cache_ttl', 'requires_restart',
                'last_changed_at', 'change_count', 'default_value',
                'min_value', 'max_value', 'validation_message', 'help_text',
                'is_readonly', 'is_system', 'is_visible', 'metadata'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('system_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('activity_log', function (Blueprint $table) {
            // Drop indices
            $table->dropIndex(['user_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['severity']);
            $table->dropIndex(['event']);
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['status_code']);
            $table->dropIndex(['is_read']);
            $table->dropIndex(['is_important']);
            $table->dropIndex(['is_archived']);
            $table->dropIndex(['created_at', 'severity']);
            $table->dropIndex(['user_id', 'created_at']);

            $columns = [
                'user_id', 'type', 'severity', 'ip_address', 'user_agent',
                'method', 'url', 'status_code', 'response_time', 'session_id',
                'old_values', 'new_values', 'changes', 'tags', 'context',
                'is_read', 'read_at', 'is_important', 'is_archived',
                'archived_at', 'team_id', 'company_id'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('activity_log', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};