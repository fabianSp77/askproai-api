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
            if (!Schema::hasColumn('companies', 'industry')) {
                $table->string('industry', 50)->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'subscription_status')) {
                $table->string('subscription_status', 50)->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'subscription_plan')) {
                $table->string('subscription_plan', 50)->nullable();
            }
            
            // Index for faster queries
            $table->index('industry');
            $table->index('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
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