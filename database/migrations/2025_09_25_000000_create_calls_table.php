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
        if (Schema::hasTable('calls')) {
            return;
        }

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('phone_number_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('retell_call_id')->unique();
            $table->string('status')->default('ongoing');
            $table->string('call_status')->nullable();
            $table->string('direction')->nullable();
            $table->integer('duration_sec')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->timestamp('call_time')->nullable();
            $table->boolean('call_successful')->default(false);
            $table->boolean('appointment_made')->default(false);
            $table->string('sentiment')->nullable();
            $table->decimal('sentiment_score', 3, 2)->nullable();
            $table->text('summary')->nullable();
            $table->json('analysis')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('cost_cents')->nullable();
            $table->integer('base_cost')->nullable();
            $table->integer('reseller_cost')->nullable();
            $table->integer('customer_cost')->nullable();
            $table->integer('platform_profit')->nullable();
            $table->integer('reseller_profit')->nullable();
            $table->integer('total_profit')->nullable();
            $table->decimal('profit_margin_platform', 5, 2)->nullable();
            $table->decimal('profit_margin_reseller', 5, 2)->nullable();
            $table->decimal('profit_margin_total', 5, 2)->nullable();
            $table->string('cost_calculation_method')->nullable();
            $table->json('cost_breakdown')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('transcript')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('phone_number_id')->references('id')->on('phone_numbers')->onDelete('set null');

            // Indexes
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('phone_number_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
