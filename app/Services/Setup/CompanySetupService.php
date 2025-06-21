<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanySetupService
{
    /**
     * Create a new company with transaction safety
     */
    public function createCompany(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $company = Company::create([
                'name' => $data['company_name'],
                'industry' => $data['industry'],
                'email' => $data['company_email'] ?? null,
                'website' => $data['company_website'] ?? null,
                'description' => $data['company_description'] ?? null,
                'is_active' => true,
                'settings' => [
                    'wizard_completed' => true,
                    'setup_date' => now()->toISOString(),
                    'industry_template' => $data['industry'],
                    'default_appointment_duration' => $this->getDefaultDuration($data['industry']),
                    'default_buffer_time' => $this->getDefaultBuffer($data['industry']),
                ]
            ]);

            Log::info('Company created via QuickSetupWizard', [
                'company_id' => $company->id,
                'name' => $company->name,
                'industry' => $company->industry
            ]);

            return $company;
        });
    }

    /**
     * Create multiple branches efficiently
     */
    public function createBranches(Company $company, array $branchesData): array
    {
        $branchModels = [];
        
        foreach ($branchesData as $branchData) {
            $branchModels[] = new Branch([
                'company_id' => $company->id,
                'name' => $branchData['name'],
                'city' => $branchData['city'] ?? null,
                'address' => $branchData['address'] ?? null,
                'phone_number' => $branchData['phone_number'] ?? null,
                'email' => $branchData['email'] ?? null,
                'is_active' => true,
                'business_hours' => $this->getDefaultBusinessHours($company->industry),
                'settings' => [
                    'features' => $branchData['features'] ?? [],
                    'capacity' => $branchData['capacity'] ?? 10,
                ]
            ]);
        }

        // Bulk save for performance
        $company->branches()->saveMany($branchModels);

        Log::info('Branches created via QuickSetupWizard', [
            'company_id' => $company->id,
            'count' => count($branchModels)
        ]);

        return $branchModels;
    }

    private function getDefaultDuration(string $industry): int
    {
        return match($industry) {
            'medical' => 30,
            'beauty' => 60,
            'handwerk' => 120,
            'legal' => 45,
            default => 30
        };
    }

    private function getDefaultBuffer(string $industry): int
    {
        return match($industry) {
            'medical' => 10,
            'beauty' => 15,
            'handwerk' => 30,
            'legal' => 15,
            default => 15
        };
    }

    private function getDefaultBusinessHours(string $industry): array
    {
        return match($industry) {
            'medical' => [
                'monday' => ['09:00-12:00', '14:00-18:00'],
                'tuesday' => ['09:00-12:00', '14:00-18:00'],
                'wednesday' => ['09:00-12:00', '14:00-18:00'],
                'thursday' => ['09:00-12:00', '14:00-18:00'],
                'friday' => ['09:00-12:00', '14:00-17:00'],
                'saturday' => [],
                'sunday' => []
            ],
            'beauty' => [
                'monday' => ['10:00-19:00'],
                'tuesday' => ['10:00-19:00'],
                'wednesday' => ['10:00-19:00'],
                'thursday' => ['10:00-20:00'],
                'friday' => ['10:00-20:00'],
                'saturday' => ['09:00-16:00'],
                'sunday' => []
            ],
            default => [
                'monday' => ['09:00-17:00'],
                'tuesday' => ['09:00-17:00'],
                'wednesday' => ['09:00-17:00'],
                'thursday' => ['09:00-17:00'],
                'friday' => ['09:00-17:00'],
                'saturday' => [],
                'sunday' => []
            ]
        };
    }
}