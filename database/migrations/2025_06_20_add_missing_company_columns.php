<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'payment_terms')) {
                $table->string('payment_terms', 20)->default('net30');
            }
            
            if (!Schema::hasColumn('companies', 'small_business_threshold_date')) {
                $table->date('small_business_threshold_date')->nullable();
            }
        });
    }
    
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['payment_terms', 'small_business_threshold_date']);
        });
    }
};