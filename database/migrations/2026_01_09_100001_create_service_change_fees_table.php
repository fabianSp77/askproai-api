<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing System Extension: Service Change Fees
     *
     * Tracks professional service fees for configuration changes:
     * - AI Agent prompt/behavior modifications
     * - Call flow changes
     * - Service Gateway configuration
     * - Custom integrations
     *
     * These are manually entered fees, NOT automated event-based charges.
     */
    public function up(): void
    {
        Schema::create('service_change_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Fee categorization
            $table->enum('category', [
                'agent_change',      // AI Agent prompt/behavior modifications
                'flow_change',       // Call flow node/logic changes
                'gateway_config',    // Service Gateway configuration
                'integration',       // Custom integration work
                'support',           // Technical support beyond scope
                'custom',            // Other professional services
            ]);

            // Amount
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // Description
            $table->string('title');
            $table->text('description')->nullable();

            // Status tracking
            $table->enum('status', ['pending', 'invoiced', 'paid', 'waived', 'cancelled'])
                ->default('pending');

            // Invoice linkage
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable();

            // Transaction linkage (when deducted from balance)
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();

            // Work tracking
            $table->date('service_date')->nullable()->comment('When the work was performed');
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();

            // Related entities (for context)
            $table->string('related_entity_type')->nullable()->comment('e.g., RetellAgent, ConversationFlow');
            $table->unsignedBigInteger('related_entity_id')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['category', 'status']);
            $table->index('service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_change_fees');
    }
};
