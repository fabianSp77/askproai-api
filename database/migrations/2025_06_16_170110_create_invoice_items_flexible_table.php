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
        $this->createTableIfNotExists('invoice_items_flexible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['service', 'usage', 'tax', 'discount', 'adjustment']);
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('unit');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            // Only add foreign key if tax_rates table exists
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            if (Schema::hasTable('tax_rates')) {
                $table->foreign('tax_rate_id')->references('id')->on('tax_rates');
            }
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $this->addJsonColumn($table, 'metadata', true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['invoice_id', 'sort_order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('invoice_items_flexible');
    }
};