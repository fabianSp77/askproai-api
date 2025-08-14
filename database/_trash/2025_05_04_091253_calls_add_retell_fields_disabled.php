<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->uuid('external_id')->nullable()->after('id')->index();
            $table->string('call_status')->nullable()->after('external_id');
            $table->boolean('call_successful')->nullable()->after('call_status');
            $table->unsignedInteger('duration_sec')->nullable()->after('call_successful');
            $table->json('analysis')->nullable()->after('duration_sec');
            $table->longText('transcript')->nullable()->after('analysis');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'external_id',
                'call_status',
                'call_successful',
                'duration_sec',
                'analysis',
                'transcript',
            ]);
        });
    }
};
