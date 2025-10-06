<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\PhoneNumberNormalizer;
use App\Models\PhoneNumber;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds number_normalized column to phone_numbers table for consistent E.164 format lookups.
     * This fixes the critical issue where webhook phone numbers don't match due to format differences.
     */
    public function up(): void
    {
        // Add the normalized column
        
        if (!Schema::hasTable('phone_numbers')) {
            return;
        }

        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->string('number_normalized', 20)->nullable()
                ->comment('E.164 normalized format for consistent lookups');
            $table->index('number_normalized', 'idx_phone_numbers_normalized');
        });

        // Migrate existing data to normalized format
        $this->migrateExistingPhoneNumbers();
    }

    /**
     * Normalize all existing phone numbers
     */
    private function migrateExistingPhoneNumbers(): void
    {
        $phoneNumbers = PhoneNumber::whereNull('number_normalized')->get();

        $updated = 0;
        $failed = 0;

        foreach ($phoneNumbers as $phoneNumber) {
            try {
                $normalized = PhoneNumberNormalizer::normalize($phoneNumber->number);

                if ($normalized) {
                    $phoneNumber->number_normalized = $normalized;
                    $phoneNumber->save();
                    $updated++;
                } else {
                    $failed++;
                    Log::warning('Could not normalize phone number', [
                        'id' => $phoneNumber->id,
                        'number' => $phoneNumber->number,
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Error normalizing phone number', [
                    'id' => $phoneNumber->id,
                    'number' => $phoneNumber->number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Phone number normalization migration complete', [
            'updated' => $updated,
            'failed' => $failed,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropIndex('idx_phone_numbers_normalized');
            $table->dropColumn('number_normalized');
        });
    }
};
