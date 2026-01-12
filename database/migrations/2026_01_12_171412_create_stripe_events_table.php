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
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique()->comment('Stripe event ID (evt_xxx)');
            $table->string('event_type')->index()->comment('Event type (invoice.paid, invoice.payment_failed, etc.)');
            $table->timestamp('processed_at')->index()->comment('When this event was processed');
            $table->timestamps();

            // Index for finding processed events quickly
            $table->index(['event_type', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};
