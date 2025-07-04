<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('webhook_events', 'company_id')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('provider');
                $table->index('company_id');
                
                // Add foreign key if companies table exists
                if (Schema::hasTable('companies')) {
                    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            // Drop foreign key first if it exists
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Ignore if foreign key doesn't exist
            }
            
            // Drop column if it exists
            if (Schema::hasColumn('webhook_events', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};