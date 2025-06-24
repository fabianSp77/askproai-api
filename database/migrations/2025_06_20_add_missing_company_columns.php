<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['payment_terms', 'small_business_threshold_date']);
        });
    }
};