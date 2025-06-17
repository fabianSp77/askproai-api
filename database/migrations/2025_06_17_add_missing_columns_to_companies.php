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
        Schema::table('companies', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('companies', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('slug');
            }
            
            if (!Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('companies', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            
            if (!Schema::hasColumn('companies', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('state');
            }
            
            if (!Schema::hasColumn('companies', 'country')) {
                $table->string('country', 2)->default('DE')->after('postal_code');
            }
            
            if (!Schema::hasColumn('companies', 'timezone')) {
                $table->string('timezone', 50)->default('Europe/Berlin')->after('country');
            }
            
            if (!Schema::hasColumn('companies', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('timezone');
            }
            
            if (!Schema::hasColumn('companies', 'settings')) {
                $table->json('settings')->nullable()->after('currency');
            }
            
            if (!Schema::hasColumn('companies', 'metadata')) {
                $table->json('metadata')->nullable()->after('settings');
            }
            
            if (!Schema::hasColumn('companies', 'google_calendar_credentials')) {
                $table->text('google_calendar_credentials')->nullable()->after('calcom_user_id');
            }
            
            if (!Schema::hasColumn('companies', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('google_calendar_credentials');
            }
            
            if (!Schema::hasColumn('companies', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            }
            
            // Add indexes
            $table->index('slug');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['country']);
            
            $table->dropColumn([
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
            ]);
        });
    }
};