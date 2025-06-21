<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffSetupService
{
    /**
     * Create default staff for branches
     */
    public function createDefaultStaff(Company $company, array $branches, array $services, array $staffData): array
    {
        $staffMembers = [];

        foreach ($branches as $branch) {
            $staff = Staff::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $staffData['name'] ?? 'Standard Mitarbeiter',
                'email' => $staffData['email'] ?? $this->generateStaffEmail($branch),
                'role' => 'staff',
                'is_active' => true,
                'calendar_connected' => false,
                'working_hours' => $branch->business_hours ?? $this->getDefaultWorkingHours(),
                'settings' => [
                    'auto_accept_bookings' => true,
                    'notification_email' => true,
                    'notification_sms' => false,
                ]
            ]);

            // Assign all services to staff
            if (!empty($services)) {
                $serviceIds = collect($services)->pluck('id')->toArray();
                $staff->services()->attach($serviceIds);
            }

            $staffMembers[] = $staff;
            
            Log::info('Default staff created for branch', [
                'staff_id' => $staff->id,
                'branch_id' => $branch->id,
                'services_count' => count($services)
            ]);
        }

        return $staffMembers;
    }

    /**
     * Generate staff email based on branch
     */
    private function generateStaffEmail(Branch $branch): string
    {
        $domain = $branch->company->email 
            ? substr(strrchr($branch->company->email, "@"), 1)
            : 'example.com';
            
        $branchSlug = Str::slug($branch->name);
        return "mitarbeiter.{$branchSlug}@{$domain}";
    }

    /**
     * Get default working hours
     */
    private function getDefaultWorkingHours(): array
    {
        return [
            'monday' => ['09:00-17:00'],
            'tuesday' => ['09:00-17:00'],
            'wednesday' => ['09:00-17:00'],
            'thursday' => ['09:00-17:00'],
            'friday' => ['09:00-17:00'],
            'saturday' => [],
            'sunday' => []
        ];
    }

    /**
     * Import staff from CSV
     */
    public function importFromCsv(Company $company, string $csvPath): array
    {
        // TODO: Implement CSV import functionality
        return [];
    }
}