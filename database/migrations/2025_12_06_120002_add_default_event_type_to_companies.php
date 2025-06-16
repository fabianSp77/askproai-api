<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'default_event_type_id')) {
                $table->unsignedBigInteger('default_event_type_id')->nullable()->after('calcom_user_id');
                $table->foreign('default_event_type_id')->references('id')->on('calcom_event_types')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'default_event_type_id')) {
                $table->dropForeign(['default_event_type_id']);
                $table->dropColumn('default_event_type_id');
            }
        });
    }
};