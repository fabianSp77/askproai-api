<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the calcom_event_type_id column exists before trying to clean it
        if (!Schema::hasColumn('services', 'calcom_event_type_id')) {
            \Log::info('[Cal.com Cleanup] Skipping - calcom_event_type_id column does not exist');
            return;
        }

        // Clean up fake Cal.com Event IDs
        // Fake IDs are:
        // 1. Sequential integers (1, 2, 3, etc.)
        // 2. IDs starting with 'cal_' followed by uniqid()

        // First, log what we're about to clean
        $fakeIds = DB::table('services')
            ->whereNotNull('calcom_event_type_id')
            ->where(function ($query) {
                // Check for sequential integers (typically < 1000)
                $query->whereRaw('calcom_event_type_id REGEXP "^[0-9]{1,3}$"')
                    // Or check for fake cal_ prefix
                    ->orWhere('calcom_event_type_id', 'LIKE', 'cal_%');
            })
            ->pluck('calcom_event_type_id', 'id');

        \Log::warning('[Cal.com Cleanup] Removing fake Event IDs:', $fakeIds->toArray());

        // Set fake Event IDs to NULL
        DB::table('services')
            ->whereNotNull('calcom_event_type_id')
            ->where(function ($query) {
                // Remove sequential integers
                $query->whereRaw('calcom_event_type_id REGEXP "^[0-9]{1,3}$"')
                    // Remove fake cal_ prefix IDs
                    ->orWhere('calcom_event_type_id', 'LIKE', 'cal_%');
            })
            ->update(['calcom_event_type_id' => null]);

        \Log::info('[Cal.com Cleanup] Cleaned ' . count($fakeIds) . ' fake Event IDs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore fake IDs
        \Log::info('[Cal.com Cleanup] Cannot restore fake Event IDs - they were invalid');
    }
};