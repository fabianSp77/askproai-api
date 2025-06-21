<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // confirmation, reminder_24h, reminder_2h, etc
            $this->addJsonColumn($table, 'channels', false); // ['email', 'sms', 'push', 'whatsapp']
            $table->enum('status', ['sent', 'failed', 'queued']);
            $table->text('error')->nullable();
            $this->addJsonColumn($table, 'metadata', true);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'sent_at']);
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_log');
    }
};