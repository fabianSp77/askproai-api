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
        $this->createTableIfNotExists('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_customer_id')->index();
            $table->string('name')->nullable();
            $table->string('stripe_status');
            $table->string('stripe_price_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            
            // Use compatible JSON column
            $this->addJsonColumn($table, 'metadata', true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'stripe_status']);
            $table->index('current_period_end');
            
            // Foreign keys (skip for SQLite)
            if (!$this->isSQLite()) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });
        
        $this->createTableIfNotExists('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->string('stripe_subscription_item_id')->unique();
            $table->string('stripe_price_id');
            $table->string('stripe_product_id')->nullable();
            $table->integer('quantity')->default(1);
            
            // Use compatible JSON column
            $this->addJsonColumn($table, 'metadata', true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('subscription_id');
            
            // Foreign keys (skip for SQLite)
            if (!$this->isSQLite()) {
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('subscription_items');
        $this->dropTableIfExists('subscriptions');
    }
};