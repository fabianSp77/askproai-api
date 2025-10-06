<?php

namespace App\Filament\Helpers;

use App\Models\Company;
use App\Models\Branch;

class CompanyBranchHelper
{
    /**
     * Check if a company has only one branch
     */
    public static function isSingleBranchCompany(Company $company): bool
    {
        return $company->branches()->count() === 1;
    }

    /**
     * Get the single branch for a company (if it has only one)
     */
    public static function getSingleBranch(Company $company): ?Branch
    {
        if (self::isSingleBranchCompany($company)) {
            return $company->branches()->first();
        }
        return null;
    }

    /**
     * Check if system is in single-branch mode (majority have only one branch)
     */
    public static function isSystemSingleBranchMode(): bool
    {
        $totalCompanies = Company::count();
        if ($totalCompanies === 0) return true;

        $singleBranchCompanies = Company::has('branches', '=', 1)->count();

        // If 80% or more companies have only one branch, use single-branch mode
        return ($singleBranchCompanies / $totalCompanies) >= 0.8;
    }

    /**
     * Get unified data for single-branch company
     */
    public static function getUnifiedData(Company $company): array
    {
        $branch = self::getSingleBranch($company);

        if (!$branch) {
            return [];
        }

        return [
            // Company data
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company_email' => $company->email,
            'company_phone' => $company->phone,
            'billing_status' => $company->billing_status,
            'credit_balance' => $company->credit_balance,

            // Branch data
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'phone_number' => $branch->phone_number ?? $company->phone,
            'notification_email' => $branch->notification_email ?? $company->email,
            'address' => $branch->address ?? $company->address,
            'city' => $branch->city ?? $company->city,
            'postal_code' => $branch->postal_code ?? $company->postal_code,
            'country' => $branch->country ?? $company->country,

            // Integration status
            'has_retell' => !empty($company->retell_api_key),
            'has_calcom' => !empty($company->calcom_api_key) || !empty($branch->calcom_event_type_id),
            'calendar_mode' => $branch->calendar_mode,
        ];
    }

    /**
     * Get inheritance status for a field
     */
    public static function getInheritanceStatus(Branch $branch, string $field): array
    {
        $company = $branch->company;

        $inheritedFields = [
            'send_call_summaries',
            'call_summary_recipients',
            'include_transcript_in_summary',
            'include_csv_export',
            'retell_agent_id',
            'calcom_api_key',
        ];

        if (!in_array($field, $inheritedFields)) {
            return [
                'inherited' => false,
                'value' => $branch->$field,
                'source' => 'branch',
            ];
        }

        // Check if branch has override
        if (!is_null($branch->$field)) {
            return [
                'inherited' => false,
                'value' => $branch->$field,
                'source' => 'branch',
                'company_value' => $company->$field,
            ];
        }

        // Use company value
        return [
            'inherited' => true,
            'value' => $company->$field,
            'source' => 'company',
        ];
    }
}