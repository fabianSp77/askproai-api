<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            $t->unsignedInteger('duration_sec')->nullable();
            $t->string('user_sentiment')->nullable();
            $t->string('call_status')->nullable();
            $t->string('disconnection_reason')->nullable();
            $t->boolean('call_successful')->nullable();
            $t->json('dynamic_variables')->nullable();
            $t->json('analysis')->nullable();
            $t->longText('transcript')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            $t->dropColumn([
                'duration_sec','user_sentiment','call_status',
                'disconnection_reason','call_successful',
                'dynamic_variables','analysis','transcript'
            ]);
        });
    }
};
