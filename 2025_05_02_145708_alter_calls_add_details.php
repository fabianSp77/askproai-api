<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            $t->unsignedInteger('duration_sec')->nullable();
            $t->string('user_sentiment')->nullable();          // positive | neutral | negative
            $t->string('call_status')->nullable();             // ended / etc.
            $t->string('disconnection_reason')->nullable();    // user hangup / agent hangup …
            $t->boolean('call_successful')->nullable();        // true/false laut Retell
            $t->json('dynamic_variables')->nullable();         // _datum_termin, _email, …
            $t->json('analysis')->nullable();                  // kompletter call_analysis-Block
            $t->longText('transcript')->nullable();            // Freitext
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
