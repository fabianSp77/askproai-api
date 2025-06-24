<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if invoices table exists
        if (!Schema::hasTable('invoices')) {
            return;
        }
        
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'creation_mode')) {
                $table->string('creation_mode')->default('manual')->after('status');
                $table->index('creation_mode');
            }
        });
        
        // Update existing usage-based invoices
        if (Schema::hasTable('invoice_items_flexible')) {
            DB::table('invoices')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('invoice_items_flexible')
                        ->whereColumn('invoice_items_flexible.invoice_id', 'invoices.id')
                        ->where('invoice_items_flexible.type', 'usage');
                })
                ->update(['creation_mode' => 'usage']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }
        
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'creation_mode')) {
                $table->dropColumn('creation_mode');
            }
        });
    }
};