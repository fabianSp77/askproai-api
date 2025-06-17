<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTFALL-WIEDERHERSTELLUNG: Diese Tabellen wurden fälschlicherweise gelöscht
     */
    public function up(): void
    {
        // 1. Sessions Table - KRITISCH für Laravel Session Management
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
        
        // 2. Password Reset Tokens - KRITISCH für Laravel Auth
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
        
        // 3. Retell Webhooks - WICHTIG: Enthielt 1383 Records!
        if (!Schema::hasTable('retell_webhooks')) {
            Schema::create('retell_webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('event_type');
                $table->string('call_id')->nullable();
                $table->json('payload');
                $table->string('status')->default('pending');
                $table->text('error')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                
                $table->index('call_id');
                $table->index('event_type');
                $table->index('status');
            });
        }
        
        // 4. User Statuses - Hatte Foreign Key Dependencies
        if (!Schema::hasTable('user_statuses')) {
            Schema::create('user_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('status_title');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
        
        // 5. Event Type Import Logs - Wichtig für Cal.com Sync
        if (!Schema::hasTable('event_type_import_logs')) {
            Schema::create('event_type_import_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('status');
                $table->integer('total_event_types')->default(0);
                $table->integer('imported_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->json('details')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                $table->index('company_id');
                $table->index('user_id');
            });
        }
        
        // 6. Staff Event Type Assignments - Wichtig für Staff-Event Zuordnung
        if (!Schema::hasTable('staff_event_type_assignments')) {
            Schema::create('staff_event_type_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id');
                $table->unsignedBigInteger('event_type_id');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                
                $table->index('staff_id');
                $table->index('event_type_id');
                $table->unique(['staff_id', 'event_type_id']);
            });
        }
        
        // 7. Retell Agents - Möglicherweise noch verwendet
        if (!Schema::hasTable('retell_agents')) {
            Schema::create('retell_agents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('agent_id')->unique();
                $table->string('name');
                $table->json('configuration')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('company_id');
            });
        }
        
        // 8. Activity Log - Wichtig für Audit Trail
        if (!Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->id();
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject');
                $table->nullableMorphs('causer');
                $table->json('properties')->nullable();
                $table->string('event')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                
                $table->index('log_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('retell_agents');
        Schema::dropIfExists('staff_event_type_assignments');
        Schema::dropIfExists('event_type_import_logs');
        Schema::dropIfExists('user_statuses');
        Schema::dropIfExists('retell_webhooks');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};