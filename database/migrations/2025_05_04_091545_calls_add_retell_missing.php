<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // nur anlegen, wenn Spalte noch fehlt
            if (! Schema::hasColumn('calls', 'call_status')) {
                $table->string('call_status')->nullable()->after('external_id');
            }
            if (! Schema::hasColumn('calls', 'call_successful')) {
                $table->boolean('call_successful')->nullable()->after('call_status');
            }
            if (! Schema::hasColumn('calls', 'duration_sec')) {
                $table->unsignedInteger('duration_sec')->nullable()->after('call_successful');
            }
            if (! Schema::hasColumn('calls', 'analysis')) {
                $table->json('analysis')->nullable()->after('duration_sec');
            }
            if (! Schema::hasColumn('calls', 'transcript')) {
                $table->longText('transcript')->nullable()->after('analysis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'call_status',
                'call_successful',
                'duration_sec',
                'analysis',
                'transcript',
            ]);
        });
    }
};
