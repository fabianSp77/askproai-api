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
        if (!Schema::hasColumn('phone_numbers', 'retell_agent_version')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->string('retell_agent_version')->nullable()->after('retell_agent_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn('retell_agent_version');
        });
    }
};