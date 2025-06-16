<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('staff', function (Blueprint $table) {
            // calendar_mode und calendar_id existieren bereits
            // Füge nur die neuen Felder hinzu
            if (!Schema::hasColumn('staff', 'calcom_user_id')) {
                $table->string('calcom_user_id')->nullable()->after('calendar_id');
            }
            if (!Schema::hasColumn('staff', 'calcom_calendar_link')) {
                $table->string('calcom_calendar_link')->nullable()->after('calcom_user_id');
            }
            if (!Schema::hasColumn('staff', 'is_bookable')) {
                $table->boolean('is_bookable')->default(true)->after('active');
            }
        });
    }

    public function down()
    {
        Schema::table('staff', function (Blueprint $table) {
            $columns = ['calcom_user_id', 'calcom_calendar_link', 'is_bookable'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('staff', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
