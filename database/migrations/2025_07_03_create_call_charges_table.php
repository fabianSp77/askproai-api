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
        if (!Schema::hasTable('call_charges')) {
            Schema::create('call_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('call_id')->constrained('calls')->onDelete('cascade');
                $table->foreignId('company_id')->constrained('companies');
                $table->decimal('amount_charged', 10, 4)->default(0);
                $table->decimal('amount_credited', 10, 4)->default(0);
                $table->string('charge_type')->default('usage'); // usage, penalty, refund
                $table->string('currency', 3)->default('EUR');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                
                // Refund tracking fields (from the other migration)
                $table->boolean('is_refunded')->default(false);
                $table->decimal('refund_amount', 10, 4)->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->string('refund_reason')->nullable();
                $table->string('refund_initiated_by')->nullable();
                
                $table->timestamps();
                
                // Indexes
                $table->index(['company_id', 'created_at']);
                $table->index(['call_id']);
                $table->index(['charge_type']);
                $table->index(['is_refunded']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_charges');
    }
};