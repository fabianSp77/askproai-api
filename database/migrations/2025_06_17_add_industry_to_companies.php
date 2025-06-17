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
            if (!Schema::hasColumn('companies', 'industry')) {
                $table->string('industry', 50)->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable()->after('industry');
            }
            
            if (!Schema::hasColumn('companies', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('is_active');
            }
            
            if (!Schema::hasColumn('companies', 'subscription_status')) {
                $table->string('subscription_status', 50)->nullable()->after('trial_ends_at');
            }
            
            if (!Schema::hasColumn('companies', 'subscription_plan')) {
                $table->string('subscription_plan', 50)->nullable()->after('subscription_status');
            }
            
            // Index for faster queries
            $table->index('industry');
            $table->index('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['industry']);
            $table->dropIndex(['subscription_status']);
            
            $table->dropColumn([
                'industry',
                'logo', 
                'trial_ends_at',
                'subscription_status',
                'subscription_plan'
            ]);
        });
    }
};