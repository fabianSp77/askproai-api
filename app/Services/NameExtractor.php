<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Facades\Log;
use App\Services\Patterns\GermanNamePatternLibrary;
use App\ValueObjects\AnonymousCallDetector;

class NameExtractor
{
    /**
     * Extract customer name from transcript or notes
     */
    public function extractNameFromCall(Call $call): ?string
    {
        // First check if name is already in notes
        if ($call->notes) {
            $name = $this->extractNameFromNotes($call->notes);
            if ($name) {
                return $name;
            }
        }

        // Try to extract from transcript
        if ($call->transcript) {
            $name = $this->extractNameFromTranscript($call->transcript);
            if ($name) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Extract name from notes (could be JSON or plain text)
     */
    private function extractNameFromNotes(string $notes): ?string
    {
        // Check if notes is JSON
        if (str_starts_with($notes, '{') || str_starts_with($notes, '[')) {
            $decoded = json_decode($notes, true);
            if ($decoded) {
                // Try various JSON fields for name
                if (isset($decoded['full_name'])) {
                    return $decoded['full_name'];
                } elseif (isset($decoded['name'])) {
                    return $decoded['name'];
                } elseif (isset($decoded['customer_name'])) {
                    return $decoded['customer_name'];
                }
            }
        }

        // Plain text notes - look for pattern like "Hans Schuster - Beratung..."
        if (!str_contains(strtolower($notes), 'abgebrochen') &&
            !str_contains(strtolower($notes), 'kein termin')) {
            if (preg_match('/^([^-–]+)\s*[-–]/', $notes, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract name from transcript using German patterns
     */
    public function extractNameFromTranscript(string $transcript): ?string
    {
        // Use centralized GermanNamePatternLibrary for consistent name extraction
        $extractionResult = GermanNamePatternLibrary::extractWithConfidence($transcript);

        if ($extractionResult) {
            $name = $extractionResult['name'];

            // Validate it's a reasonable name
            if (strlen($name) > 2 && strlen($name) < 50 && !is_numeric($name)) {
                Log::info('Name extracted from transcript', [
                    'pattern' => $extractionResult['pattern'],
                    'confidence' => $extractionResult['confidence'],
                    'name' => $name
                ]);
                return $name;
            }
        }

        return null;
    }

    /**
     * Update call with extracted name
     */
    public function updateCallWithExtractedName(Call $call): bool
    {
        // Don't override existing customer name
        if ($call->customer_name) {
            return false;
        }

        // PRIORITY 1: Use caller_full_name from Retell analysis (most reliable)
        // This is provided by Retell agent directly, more accurate than transcript extraction
        if ($call->analysis && is_array($call->analysis)) {
            $callerName = $call->analysis['custom_analysis_data']['caller_full_name'] ??
                         $call->analysis['custom_analysis_data']['patient_full_name'] ??
                         $call->analysis['caller_full_name'] ??
                         $call->analysis['patient_full_name'] ??
                         null;

            if ($callerName && is_string($callerName) && strlen(trim($callerName)) > 0) {
                Log::info('✅ Using caller_full_name from Retell analysis (high quality)', [
                    'call_id' => $call->id,
                    'caller_full_name' => $callerName,
                    'source' => 'retell_analysis'
                ]);

                $isAnonymous = AnonymousCallDetector::isAnonymous($call);

                $call->update([
                    'customer_name' => trim($callerName),
                    'customer_name_verified' => !$isAnonymous, // Only verify if not anonymous
                    'verification_confidence' => $isAnonymous ? 50 : 99, // Higher confidence from Retell
                    'verification_method' => 'retell_agent_provided'
                ]);

                return true;
            }
        }

        // PRIORITY 2: Fallback to transcript extraction (lower quality)
        $extractedName = $this->extractNameFromCall($call);

        if ($extractedName) {
            // Use AnonymousCallDetector for consistent anonymity detection
            $isAnonymous = AnonymousCallDetector::isAnonymous($call);

            if ($isAnonymous) {
                // Anonymous call - name is NOT verified
                $call->update([
                    'customer_name' => $extractedName,
                    'customer_name_verified' => false,
                    'verification_confidence' => 0,
                    'verification_method' => 'anonymous_name'
                ]);
            } else {
                // Call with phone number - name is verified
                $call->update([
                    'customer_name' => $extractedName,
                    'customer_name_verified' => true,
                    'verification_confidence' => 99,
                    'verification_method' => 'phone_match'
                ]);
            }

            // Also update notes if needed
            if (!$call->notes || $call->notes === 'NULL' || strlen($call->notes) < 3) {
                $noteContent = '';
                // If there's appointment information, add it
                if ($call->appointment_made) {
                    $noteContent = 'Termin vereinbart';
                }
                if ($noteContent) {
                    $call->update(['notes' => $noteContent]);
                }
            }

            Log::info('Call updated with extracted name', [
                'call_id' => $call->id,
                'name' => $extractedName,
                'verified' => !$isAnonymous,
                'verification_method' => $isAnonymous ? 'anonymous_name' : 'phone_match',
                'confidence' => $isAnonymous ? 0 : 99,
                'from_number' => $call->from_number
            ]);

            return true;
        }

        return false;
    }

    /**
     * Process anonymous calls to extract names
     */
    public function processAnonymousCalls(): int
    {
        $updated = 0;

        // Use AnonymousCallDetector to find calls that should be processed
        $calls = Call::whereNull('customer_id')
            ->where(function($query) {
                $query->whereNull('notes')
                      ->orWhere('notes', '');
            })
            ->whereNotNull('transcript')
            ->get()
            ->filter(fn($call) => AnonymousCallDetector::isAnonymous($call));

        foreach ($calls as $call) {
            if ($this->updateCallWithExtractedName($call)) {
                $updated++;
            }
        }

        Log::info('Processed anonymous calls for name extraction', [
            'total_processed' => $calls->count(),
            'updated' => $updated
        ]);

        return $updated;
    }

    /**
     * Check if a name matches an existing customer
     * BUT only link if we have matching phone number
     */
    public function findCustomerMatch(Call $call, string $name): ?int
    {
        // IMPORTANT: For anonymous calls, DO NOT link to customers
        if (AnonymousCallDetector::isAnonymous($call)) {
            Log::info('Not linking anonymous call to customer', [
                'call_id' => $call->id,
                'name' => $name,
                'reason' => AnonymousCallDetector::getReason($call)
            ]);
            return null;
        }

        // Only link if we have a real phone number match
        $customer = \App\Models\Customer::where('phone', $call->from_number)
            ->first();

        if ($customer) {
            Log::info('Found customer match by phone', [
                'call_id' => $call->id,
                'customer_id' => $customer->id,
                'phone' => $call->from_number
            ]);
            return $customer->id;
        }

        return null;
    }
}