<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (! Schema::hasColumn('calls', 'duration_sec')) {
                $table->unsignedInteger('duration_sec')->nullable()->after('to_number');
            }
            if (! Schema::hasColumn('calls', 'tmp_call_id')) {
                $table->uuid('tmp_call_id')->nullable()->after('retell_call_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['duration_sec', 'tmp_call_id']);
        });
    }
};
