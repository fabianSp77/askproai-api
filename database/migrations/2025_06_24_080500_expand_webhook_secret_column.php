<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('retell_configurations')) {
            Schema::table('retell_configurations', function (Blueprint $table) {
                $table->text('webhook_secret')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('retell_configurations')) {
            Schema::table('retell_configurations', function (Blueprint $table) {
                $table->string('webhook_secret', 255)->nullable()->change();
            });
        }
    }
};