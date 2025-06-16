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
        Schema::table('companies', function (Blueprint $table) {
            // Encrypted data needs more space - typically 3-4x the original size
            $table->text('calcom_api_key')->nullable()->change();
            $table->text('retell_api_key')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Note: This might fail if data is too long
            $table->string('calcom_api_key', 255)->nullable()->change();
            $table->string('retell_api_key', 255)->nullable()->change();
        });
    }
};