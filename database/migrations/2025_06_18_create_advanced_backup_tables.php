<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        // External sync logs
        Schema::create('external_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 50); // full, incremental, calcom, retell
            $this->addJsonColumn($table, 'report', false);
            $table->string('status', 20);
            $table->timestamps();
            $table->index(['sync_type', 'created_at']);
        });
        
        // Cal.com backup data
        Schema::create('calcom_bookings_backup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('calcom_booking_id');
            $this->addJsonColumn($table, 'booking_data', false);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 50);
            $table->string('attendee_email')->nullable();
            $table->timestamp('synced_at');
            $table->unique(['calcom_booking_id']);
            $table->index(['company_id', 'starts_at']);
        });
        
        Schema::create('calcom_event_types_backup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('calcom_event_type_id');
            $this->addJsonColumn($table, 'event_type_data', false);
            $table->timestamp('synced_at');
            $table->unique(['calcom_event_type_id']);
        });
        
        // Retell.ai backup data
        Schema::create('retell_calls_backup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('retell_call_id');
            $this->addJsonColumn($table, 'call_data', false);
            $table->text('transcript')->nullable();
            $table->string('recording_url')->nullable();
            $table->integer('duration_seconds');
            $table->string('from_number');
            $table->string('to_number');
            $table->timestamp('synced_at');
            $table->unique(['retell_call_id']);
            $table->index(['company_id', 'synced_at']);
        });
        
        Schema::create('retell_agents_backup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('retell_agent_id');
            $this->addJsonColumn($table, 'agent_data', false);
            $table->timestamp('synced_at');
            $table->unique(['retell_agent_id']);
        });
        
        // Billing snapshots
        Schema::create('billing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('period', 7); // YYYY-MM
            $this->addJsonColumn($table, 'snapshot_data', false);
            $table->string('checksum', 64);
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'period']);
            $table->index('is_finalized');
        });
        
        Schema::create('billing_line_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billing_snapshot_id');
            $table->string('branch_id', 36);
            $table->string('item_type', 50);
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
            $table->index(['billing_snapshot_id', 'branch_id']);
        });
        
        Schema::create('billing_snapshots_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id');
            $this->addJsonColumn($table, 'snapshot_data', false);
            $table->string('checksum', 64);
            $table->timestamp('archived_at');
            $table->index('original_id');
        });
        
        // Audit trail for critical operations
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->string('entity_id');
            $this->addJsonColumn($table, 'old_values', true);
            $this->addJsonColumn($table, 'new_values', true);
            $table->string('ip_address', 45);
            $table->string('user_agent');
            $table->timestamp('created_at');
            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('billing_snapshots_archive');
        Schema::dropIfExists('billing_line_items');
        Schema::dropIfExists('billing_snapshots');
        Schema::dropIfExists('retell_agents_backup');
        Schema::dropIfExists('retell_calls_backup');
        Schema::dropIfExists('calcom_event_types_backup');
        Schema::dropIfExists('calcom_bookings_backup');
        Schema::dropIfExists('external_sync_logs');
    }
};