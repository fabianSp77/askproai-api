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
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('webhook_events', 'headers')) {
                $table->json('headers')->nullable()->after('payload');
            }
            
            if (!Schema::hasColumn('webhook_events', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('correlation_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_events', 'headers')) {
                $table->dropColumn('headers');
            }
            
            if (Schema::hasColumn('webhook_events', 'received_at')) {
                $table->dropColumn('received_at');
            }
        });
    }
};
