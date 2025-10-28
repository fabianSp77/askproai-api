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
        Schema::table('services', function (Blueprint $table) {
            // Processing Time Feature (inspired by Fresha, Square Appointments, Schedulista)
            // Allows staff to be available during treatment gaps (e.g. hair color processing time)
            $table->boolean('has_processing_time')->default(false)->after('duration_minutes')
                ->comment('Service has processing/gap time where staff is available for other clients');

            $table->integer('initial_duration')->nullable()->after('has_processing_time')
                ->comment('First phase duration (e.g. apply color) - Staff BUSY');

            $table->integer('processing_duration')->nullable()->after('initial_duration')
                ->comment('Processing/gap duration (e.g. color processing) - Staff AVAILABLE');

            $table->integer('final_duration')->nullable()->after('processing_duration')
                ->comment('Final phase duration (e.g. rinse, cut, style) - Staff BUSY');

            // Index for filtering processing time services
            $table->index('has_processing_time', 'services_has_processing_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_has_processing_time_index');
            $table->dropColumn([
                'has_processing_time',
                'initial_duration',
                'processing_duration',
                'final_duration',
            ]);
        });
    }
};
