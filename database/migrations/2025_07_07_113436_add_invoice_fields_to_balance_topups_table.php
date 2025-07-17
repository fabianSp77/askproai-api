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
        Schema::table('balance_topups', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('paid_at');
            $table->string('stripe_invoice_id')->nullable()->after('invoice_id');
            
            // Add indexes
            $table->index('invoice_id');
            $table->index('stripe_invoice_id');
            
            // Add foreign key
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balance_topups', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['stripe_invoice_id']);
            $table->dropColumn(['invoice_id', 'stripe_invoice_id']);
        });
    }
};