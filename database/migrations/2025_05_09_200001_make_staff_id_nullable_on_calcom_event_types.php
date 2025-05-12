<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->uuid('staff_id')->nullable()->change();   // NULL erlaubt
        });
    }

    public function down(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->uuid('staff_id')->nullable(false)->change(); // wieder NOT NULL
        });
    }
};
