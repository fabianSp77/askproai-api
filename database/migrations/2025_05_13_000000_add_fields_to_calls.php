<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('duration_sec')->nullable();
            $table->uuid('tmp_call_id')->nullable()->unique();
            $table->json('analysis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['duration_sec', 'tmp_call_id', 'analysis']);
        });
    }
};
