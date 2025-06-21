<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $this->addJsonColumn($table, 'api_test_errors', true);
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('api_test_errors');
        });
    }
};
