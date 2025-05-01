<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /* 1)  alte Constraints sicher entfernen -------------- */
        DB::statement("SET foreign_key_checks = 0");
        DB::statement("ALTER TABLE integrations 
                       DROP FOREIGN KEY IF EXISTS integrations_kunde_id_foreign");
        DB::statement("ALTER TABLE integrations 
                       DROP FOREIGN KEY IF EXISTS integrations_customer_id_foreign");
        DB::statement("SET foreign_key_checks = 1");

        /* 2)  Spaltenname vereinheitlichen ------------------- */
        Schema::table('integrations', function (Blueprint $table) {
            if (Schema::hasColumn('integrations', 'kunde_id') &&
                ! Schema::hasColumn('integrations', 'customer_id')) {
                $table->renameColumn('kunde_id', 'customer_id');
            }
        });

        /* 3)  richtigen FK auf customers.id anlegen ---------- */
        Schema::table('integrations', function (Blueprint $table) {
            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            if (! Schema::hasColumn('integrations', 'kunde_id')) {
                $table->renameColumn('customer_id', 'kunde_id');
            }
            $table->foreign('kunde_id')
                  ->references('id')->on('kunden')
                  ->cascadeOnDelete();
        });
    }
};
