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
        Schema::table('companies', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('companies', 'slug')) {
                $table->string('slug')->unique()->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'state')) {
                $table->string('state')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'postal_code')) {
                $table->string('postal_code', 20)->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'country')) {
                $table->string('country', 2)->default('DE');
            }
            
            if (!Schema::hasColumn('companies', 'timezone')) {
                $table->string('timezone', 50)->default('Europe/Berlin');
            }
            
            if (!Schema::hasColumn('companies', 'currency')) {
                $table->string('currency', 3)->default('EUR');
            }
            
            if (!Schema::hasColumn('companies', 'settings')) {
                $this->addJsonColumn($table, 'settings', true);
            }
            
            if (!Schema::hasColumn('companies', 'metadata')) {
                $this->addJsonColumn($table, 'metadata', true);
            }
            
            if (!Schema::hasColumn('companies', 'google_calendar_credentials')) {
                $table->text('google_calendar_credentials')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable();
            }
            
            // Add indexes (skip for SQLite to avoid issues)
            if (\DB::getDriverName() !== 'sqlite') {
                $table->index('slug');
                $table->index('country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (\DB::getDriverName() !== 'sqlite') {
                $table->dropIndex(['slug']);
                $table->dropIndex(['country']);
            }
            
            $columns = [
                'slug',
                'website',
                'city',
                'state',
                'postal_code',
                'country',
                'timezone',
                'currency',
                'settings',
                'metadata',
                'google_calendar_credentials',
                'stripe_customer_id',
                'stripe_subscription_id'
            ];
            
            // Drop columns that exist
            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};