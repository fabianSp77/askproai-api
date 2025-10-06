<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing call records that have NULL phone_number_id
        $calls = DB::table('calls')
            ->whereNull('phone_number_id')
            ->whereNotNull('to_number')
            ->where('to_number', '!=', 'unknown')
            ->get();

        Log::info('Found ' . $calls->count() . ' calls to fix phone_number_id');

        foreach ($calls as $call) {
            // Clean the phone number
            $cleanedNumber = preg_replace('/[^0-9+]/', '', $call->to_number);

            // Try exact match first
            $phoneNumber = DB::table('phone_numbers')
                ->where('number', $cleanedNumber)
                ->first();

            // If no exact match, try partial match (last 10 digits)
            if (!$phoneNumber) {
                $phoneNumber = DB::table('phone_numbers')
                    ->where('number', 'LIKE', '%' . substr($cleanedNumber, -10))
                    ->first();
            }

            if ($phoneNumber) {
                // Update the call with phone_number_id and company_id
                DB::table('calls')
                    ->where('id', $call->id)
                    ->update([
                        'phone_number_id' => $phoneNumber->id,
                        'company_id' => $phoneNumber->company_id,
                        'updated_at' => now()
                    ]);

                Log::info('Updated call ' . $call->id . ' with phone_number_id ' . $phoneNumber->id . ' and company_id ' . $phoneNumber->company_id);
            } else {
                Log::warning('Could not find phone number for call ' . $call->id . ' with to_number ' . $call->to_number);
            }
        }

        // Also fix calls with NULL company_id but valid phone_number_id
        $callsWithPhoneButNoCompany = DB::table('calls')
            ->whereNull('company_id')
            ->whereNotNull('phone_number_id')
            ->get();

        Log::info('Found ' . $callsWithPhoneButNoCompany->count() . ' calls to fix company_id');

        foreach ($callsWithPhoneButNoCompany as $call) {
            $phoneNumber = DB::table('phone_numbers')
                ->where('id', $call->phone_number_id)
                ->first();

            if ($phoneNumber && $phoneNumber->company_id) {
                DB::table('calls')
                    ->where('id', $call->id)
                    ->update([
                        'company_id' => $phoneNumber->company_id,
                        'updated_at' => now()
                    ]);

                Log::info('Updated call ' . $call->id . ' with company_id ' . $phoneNumber->company_id);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need to be reversed
        // The data was already broken, we're just fixing it
    }
};