<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individual line items for Aggregate Invoices
     *
     * Each item represents a charge for a specific company managed by the partner.
     * Items are grouped by company in the final invoice display.
     *
     * Example structure:
     *   Friseur Schmidt GmbH
     *     - Call Minutes (142 calls)     €45.20
     *     - Monthly Service Fee          €200.00
     *   Autohaus Müller AG
     *     - Call Minutes (89 calls)      €89.50
     *     - Call Flow Change             €500.00
     */
    public function up(): void
    {
        Schema::create('aggregate_invoice_items', function (Blueprint $table) {
            $table->id();

            // Parent invoice
            $table->foreignId('aggregate_invoice_id')
                ->constrained('aggregate_invoices')
                ->cascadeOnDelete();

            // Company this charge belongs to
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Stripe Integration
            $table->string('stripe_line_item_id')->nullable();

            // Item type for categorization
            $table->enum('item_type', [
                'call_minutes',      // Usage-based call charges
                'monthly_service',   // Recurring service fees
                'setup_fee',         // One-time setup charges
                'service_change',    // Professional service fees
                'custom',            // Manual adjustments
            ]);

            // Description shown on invoice
            $table->string('description');
            $table->string('description_detail')->nullable(); // e.g., "142 calls"

            // Pricing
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit')->nullable(); // e.g., "minutes", "calls", "hours"
            $table->unsignedBigInteger('unit_price_cents')->default(0);
            $table->unsignedBigInteger('amount_cents')->default(0);

            // Reference to source record (optional)
            $table->string('reference_type')->nullable(); // e.g., 'App\Models\Call'
            $table->unsignedBigInteger('reference_id')->nullable();

            // Period this item covers (for usage items)
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Additional data
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['aggregate_invoice_id', 'company_id']);
            $table->index(['company_id', 'item_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_invoice_items');
    }
};
