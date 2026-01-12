<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add foreign key constraint for service_cases.invoice_item_id
 *
 * This ensures referential integrity between service_cases and aggregate_invoice_items.
 * Previously, invoice_item_id was just an unsignedBigInteger without constraint,
 * which could lead to orphaned records.
 *
 * Note: Uses SET NULL on delete to preserve billing history even if invoice item is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->foreign('invoice_item_id')
                ->references('id')
                ->on('aggregate_invoice_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->dropForeign(['invoice_item_id']);
        });
    }
};
