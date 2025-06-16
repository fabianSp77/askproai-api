<?php

namespace App\Services;

use App\Models\Company;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class CompanyService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get company settings with caching
     */
    public function getSettings(int $companyId): ?array
    {
        return $this->cacheService->getCompanySettings($companyId, function () use ($companyId) {
            $company = Company::find($companyId);
            
            if (!$company) {
                return null;
            }

            return [
                'id' => $company->id,
                'name' => $company->name,
                'settings' => $company->settings ?? [],
                'calendar_mode' => $company->calendar_mode,
                'calendar_mapping' => $company->calendar_mapping ?? [],
                'notification_emails' => $company->notification_emails ?? [],
                'notify_on_booking' => $company->notify_on_booking,
                'send_booking_confirmations' => $company->send_booking_confirmations,
                'opening_hours' => $company->opening_hours ?? [],
                'calcom_api_key' => $company->calcom_api_key,
                'calcom_team_slug' => $company->calcom_team_slug,
                'calcom_event_type_id' => $company->calcom_event_type_id,
                'retell_api_key' => $company->retell_api_key,
                'webhook_url' => $company->webhook_url,
                'subscription_plan' => $company->subscription_plan,
                'subscription_status' => $company->subscription_status,
                'trial_ends_at' => $company->trial_ends_at,
                'is_active' => $company->is_active,
            ];
        });
    }

    /**
     * Update company settings
     */
    public function updateSettings(int $companyId, array $settings): bool
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            Log::error('Company not found for settings update', ['company_id' => $companyId]);
            return false;
        }

        // Update the settings
        $company->settings = array_merge($company->settings ?? [], $settings);
        $result = $company->save();

        // Clear cache after update
        if ($result) {
            $this->cacheService->clearCompanyCache($companyId);
        }

        return $result;
    }

    /**
     * Update company configuration
     */
    public function updateConfiguration(int $companyId, array $data): bool
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            Log::error('Company not found for configuration update', ['company_id' => $companyId]);
            return false;
        }

        // Update the configuration fields
        $company->fill($data);
        $result = $company->save();

        // Clear cache after update
        if ($result) {
            $this->cacheService->clearCompanyCache($companyId);
        }

        return $result;
    }

    /**
     * Get company by ID with caching
     */
    public function getCompany(int $companyId): ?Company
    {
        return $this->cacheService->getCompanySettings($companyId, function () use ($companyId) {
            return Company::with(['branches', 'staff'])->find($companyId);
        });
    }

    /**
     * Check if company is active
     */
    public function isActive(int $companyId): bool
    {
        $settings = $this->getSettings($companyId);
        return $settings ? ($settings['is_active'] ?? false) : false;
    }

    /**
     * Check if company is in trial
     */
    public function isInTrial(int $companyId): bool
    {
        $settings = $this->getSettings($companyId);
        
        if (!$settings || !$settings['trial_ends_at']) {
            return false;
        }

        return now()->lt($settings['trial_ends_at']);
    }

    /**
     * Get company notification emails
     */
    public function getNotificationEmails(int $companyId): array
    {
        $settings = $this->getSettings($companyId);
        return $settings ? ($settings['notification_emails'] ?? []) : [];
    }

    /**
     * Get company calendar configuration
     */
    public function getCalendarConfig(int $companyId): array
    {
        $settings = $this->getSettings($companyId);
        
        if (!$settings) {
            return [];
        }

        return [
            'mode' => $settings['calendar_mode'] ?? 'calcom',
            'mapping' => $settings['calendar_mapping'] ?? [],
            'calcom_api_key' => $settings['calcom_api_key'],
            'calcom_team_slug' => $settings['calcom_team_slug'],
            'calcom_event_type_id' => $settings['calcom_event_type_id'],
        ];
    }
}