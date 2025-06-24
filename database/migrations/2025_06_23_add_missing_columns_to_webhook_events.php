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
        Schema::table('webhook_events', function (Blueprint $table) {
            // Add correlation_id for tracking related operations
            if (!Schema::hasColumn('webhook_events', 'correlation_id')) {
                $table->string('correlation_id')->nullable()->after('company_id')->index();
            }
            
            // Add notes for storing processing notes
            if (!Schema::hasColumn('webhook_events', 'notes')) {
                $table->text('notes')->nullable()->after('error');
            }
            
            // Add retry_count for tracking retries
            if (!Schema::hasColumn('webhook_events', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('notes');
            }
            
            // Add status column for better tracking
            if (!Schema::hasColumn('webhook_events', 'status')) {
                $table->string('status', 50)->default('pending')->after('event')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn(['correlation_id', 'notes', 'retry_count', 'status']);
        });
    }
};