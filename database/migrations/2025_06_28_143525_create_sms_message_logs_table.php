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
        Schema::create('sms_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('channel', ['sms', 'whatsapp'])->index();
            $table->string('to');
            $table->string('from');
            $table->text('message');
            $table->string('twilio_sid')->nullable()->unique();
            $table->string('status')->default('queued');
            $table->decimal('price', 10, 4)->nullable();
            $table->string('price_unit', 3)->nullable();
            $table->integer('segments')->default(1);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'created_at']);
            $table->index(['customer_id', 'channel']);
            $table->index(['status', 'created_at']);
            $table->index('twilio_sid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_message_logs');
    }
};