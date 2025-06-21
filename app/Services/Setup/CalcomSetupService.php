<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Branch;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalcomSetupService
{
    protected CalcomV2Service $calcomService;

    public function __construct()
    {
        $this->calcomService = app(CalcomV2Service::class);
    }

    /**
     * Setup Cal.com integration for company
     */
    public function setupIntegration(Company $company, string $apiKey): void
    {
        // Save API key
        $company->update([
            'calcom_api_key' => encrypt($apiKey),
            'calcom_integration_active' => true,
            'calcom_last_sync' => now(),
        ]);

        // Test connection
        try {
            $this->calcomService->setApiKey($apiKey);
            $profile = $this->calcomService->getProfile();
            
            Log::info('Cal.com integration setup successful', [
                'company_id' => $company->id,
                'calcom_user' => $profile['username'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Cal.com integration setup failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Link Cal.com event type to branch
     */
    public function linkEventTypeToBranch(Branch $branch, int $eventTypeId): void
    {
        $branch->update([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_integration_active' => true,
        ]);

        Log::info('Cal.com event type linked to branch', [
            'branch_id' => $branch->id,
            'event_type_id' => $eventTypeId
        ]);
    }

    /**
     * Create default event type
     */
    public function createDefaultEventType(Company $company, array $config = []): ?int
    {
        try {
            $this->calcomService->setApiKey(decrypt($company->calcom_api_key));
            
            $eventType = $this->calcomService->createEventType([
                'title' => $config['title'] ?? 'Termin bei ' . $company->name,
                'slug' => $config['slug'] ?? Str::slug($company->name . '-termin'),
                'length' => $config['duration'] ?? 30,
                'description' => $config['description'] ?? 'Terminbuchung über unser KI-Telefonsystem',
            ]);

            return $eventType['id'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to create Cal.com event type', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}