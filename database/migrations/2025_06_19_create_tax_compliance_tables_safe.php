<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only create tax_rates if it doesn't exist
        if (!Schema::hasTable('tax_rates')) {
            $this->createTableIfNotExists('tax_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('name');
                $table->decimal('rate', 5, 2);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_system')->default(false)->comment('System-wide tax rates');
                $table->text('description')->nullable();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->string('stripe_tax_rate_id')->nullable();
                $table->timestamps();
                
                $table->index(['company_id', 'is_default']);
                $table->index('is_system');
            });
            
            // Seed default tax rates for Germany
            DB::table('tax_rates')->insert([
                [
                    'name' => 'Standard MwSt',
                    'rate' => 19.00,
                    'is_default' => true,
                    'is_system' => true,
                    'description' => 'Standard Mehrwertsteuer in Deutschland',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Reduzierte MwSt',
                    'rate' => 7.00,
                    'is_default' => false,
                    'is_system' => true,
                    'description' => 'Reduzierte Mehrwertsteuer',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Kleinunternehmer',
                    'rate' => 0.00,
                    'is_default' => false,
                    'is_system' => true,
                    'description' => 'Keine MwSt gemäß §19 UStG',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        
        // Only create invoice_items_flexible if it doesn't exist
        if (!Schema::hasTable('invoice_items_flexible')) {
            $this->createTableIfNotExists('invoice_items_flexible', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                $table->string('stripe_invoice_item_id')->nullable();
                $table->enum('type', ['service', 'usage', 'setup_fee', 'monthly_fee', 'custom']);
                $table->string('description');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit')->default('unit')->comment('Einheit: Stück, Stunden, Minuten, etc.');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('amount', 10, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $this->addJsonColumn($table, 'metadata', true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                
                $table->index(['invoice_id', 'sort_order']);
                $table->index('type');
            });
        }
        
        // Only create small_business_monitoring if it doesn't exist
        if (!Schema::hasTable('small_business_monitoring')) {
            $this->createTableIfNotExists('small_business_monitoring', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->year('year');
                $table->decimal('revenue_current', 12, 2)->default(0);
                $table->decimal('revenue_previous_year', 12, 2)->default(0);
                $table->decimal('revenue_projected', 12, 2)->default(0);
                $table->decimal('threshold_percentage', 5, 2)->comment('Prozent vom Schwellenwert');
                $table->boolean('alert_sent')->default(false);
                $table->timestamp('alert_sent_at')->nullable();
                $table->enum('status', ['safe', 'warning', 'critical', 'exceeded'])->default('safe');
                $table->timestamps();
                
                $table->unique(['company_id', 'year']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('small_business_monitoring');
        $this->dropTableIfExists('invoice_items_flexible');
        $this->dropTableIfExists('tax_rates');
    }
};