<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            // slug (falls noch nicht da)
            if (!Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }

            // api_key  (wird von Seeder benötigt)
            if (!Schema::hasColumn('tenants', 'api_key')) {
                $table->string('api_key')->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slug', 'api_key']);
        });
    }
};
