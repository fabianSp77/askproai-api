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
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('to');
            $table->string('from')->nullable();
            $table->enum('type', ['text', 'template', 'media', 'location']);
            $table->string('template_name')->nullable();
            $table->json('template_parameters')->nullable();
            $table->text('message_body')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed']);
            $table->string('whatsapp_status')->nullable();
            $table->json('errors')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->string('conversation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('appointment_id');
            $table->index('status');
            $table->index(['to', 'created_at']);
            $table->index('conversation_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};
