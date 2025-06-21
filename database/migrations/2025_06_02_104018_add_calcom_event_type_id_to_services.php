<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('calcom_event_type_id')->nullable();
            $table->index('calcom_event_type_id');
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['calcom_event_type_id']);
            $table->dropColumn('calcom_event_type_id');
        });
    }
};
