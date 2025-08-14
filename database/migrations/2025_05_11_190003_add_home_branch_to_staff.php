<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->uuid('home_branch_id')->nullable()->after('id');

            $table->foreign('home_branch_id')
                ->references('id')->on('branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['home_branch_id']);
            $table->dropColumn('home_branch_id');
        });
    }
};
