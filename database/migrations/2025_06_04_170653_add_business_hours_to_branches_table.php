<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        if (!Schema::hasColumn('branches', 'business_hours')) {
            Schema::table('branches', function (Blueprint $table) {
                $this->addJsonColumn($table, 'business_hours', true);
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
