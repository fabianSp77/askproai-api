<?php

namespace App\Services\Setup;

use App\Models\Branch;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\Log;

class PhoneSetupService
{
    /**
     * Provision phone number for a branch
     */
    public function provisionPhoneNumber(Branch $branch): ?PhoneNumber
    {
        if (!$branch->phone_number) {
            return null;
        }

        // Check if phone number already exists
        $existing = PhoneNumber::where('number', $branch->phone_number)
            ->where('company_id', $branch->company_id)
            ->first();
            
        if ($existing) {
            // Update to link with this branch
            $existing->update([
                'branch_id' => $branch->id,
                'is_primary' => true,
                'type' => 'inbound',
            ]);
            
            Log::info('Phone number linked to branch', [
                'phone' => $branch->phone_number,
                'branch_id' => $branch->id
            ]);
            
            return $existing;
        }

        // Create new phone number record
        $phoneNumber = PhoneNumber::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'number' => $branch->phone_number,
            'type' => 'inbound',
            'provider' => 'retell',
            'is_primary' => true,
            'is_active' => true,
            'capabilities' => ['voice', 'sms'],
            'settings' => [
                'greeting_message' => $this->getDefaultGreeting($branch),
                'voicemail_enabled' => false,
                'call_recording' => true,
            ]
        ]);

        Log::info('Phone number provisioned for branch', [
            'phone_id' => $phoneNumber->id,
            'number' => $phoneNumber->number,
            'branch_id' => $branch->id
        ]);

        return $phoneNumber;
    }

    /**
     * Get default greeting based on branch/company info
     */
    private function getDefaultGreeting(Branch $branch): string
    {
        $companyName = $branch->company->name;
        $branchName = $branch->name;
        
        return "Guten Tag, Sie sind verbunden mit {$companyName}, Filiale {$branchName}. Wie kann ich Ihnen helfen?";
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $number): bool
    {
        // German phone number validation
        $pattern = '/^\+49[0-9\s\-\/\(\)]{5,20}$/';
        return preg_match($pattern, $number) === 1;
    }
}