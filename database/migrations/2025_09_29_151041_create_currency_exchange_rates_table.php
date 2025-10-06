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
        if (Schema::hasTable('currency_exchange_rates')) {
            return;
        }

        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('from_currency', ['USD', 'EUR', 'GBP']);
            $table->enum('to_currency', ['USD', 'EUR', 'GBP']);
            $table->decimal('rate', 10, 6)->comment('Exchange rate');
            $table->enum('source', ['manual', 'ecb', 'fixer', 'openexchange'])->default('manual')
                ->comment('Source of the exchange rate');
            $table->datetime('valid_from')->comment('When this rate becomes valid');
            $table->datetime('valid_until')->nullable()->comment('When this rate expires');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Additional data from API');
            $table->timestamps();

            // Indexes with shorter names
            $table->index(['from_currency', 'to_currency', 'is_active'], 'idx_currency_pair_active');
            $table->index(['valid_from', 'valid_until'], 'idx_validity_period');
            $table->unique(['from_currency', 'to_currency', 'valid_from'], 'uniq_currency_pair_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
