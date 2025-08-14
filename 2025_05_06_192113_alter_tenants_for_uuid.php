<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            /* id → char(36) (falls noch INT) */
            $column = Schema::getColumnType('tenants', 'id');
            if ($column !== 'char') {
                $table->char('id', 36)->change();
            }

            /* slug & api_key */
            if (! Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }
            if (! Schema::hasColumn('tenants', 'api_key')) {
                $table->string('api_key', 64)->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        // nur Rückbau der Zusatz-Spalten (id-Typ lassen wir dann int)
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slug', 'api_key']);
        });
    }
};
