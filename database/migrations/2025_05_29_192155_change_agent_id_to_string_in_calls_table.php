<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('agent_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->integer('agent_id')->nullable()->change();
        });
    }
};
