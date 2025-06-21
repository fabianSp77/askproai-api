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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'creation_mode')) {
                $table->string('creation_mode')->default('manual')->after('status');
                $table->index('creation_mode');
            }
        });
        
        // Update existing usage-based invoices
        DB::table('invoices')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoice_items_flexible')
                    ->whereColumn('invoice_items_flexible.invoice_id', 'invoices.id')
                    ->where('invoice_items_flexible.type', 'usage');
            })
            ->update(['creation_mode' => 'usage']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('creation_mode');
        });
    }
};