<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('branches', 'business_hours')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->json('business_hours')->nullable()->after('email');
            });
        }
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('business_hours');
        });
    }
};
