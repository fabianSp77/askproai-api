<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('calcom_team_slug')
                  ->nullable()
                  ->after('api_key')
                  ->index()
                  ->comment('Der Team-Slug des Mandanten bei Cal.com');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('calcom_team_slug');
        });
    }
};
