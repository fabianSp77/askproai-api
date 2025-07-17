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
        Schema::table('call_charges', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('amount_charged');
            $table->enum('refund_status', ['none', 'partial', 'full'])->default('none')->after('refunded_amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_status');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            $table->unsignedBigInteger('refund_transaction_id')->nullable()->after('refund_reason');
            
            $table->index('refund_status');
            $table->index('refunded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_charges', function (Blueprint $table) {
            $table->dropIndex(['refund_status']);
            $table->dropIndex(['refunded_at']);
            
            $table->dropColumn([
                'refunded_amount',
                'refund_status',
                'refunded_at',
                'refund_reason',
                'refund_transaction_id'
            ]);
        });
    }
};
