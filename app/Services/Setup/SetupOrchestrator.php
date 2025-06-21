<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Setup\CompanySetupService;
use App\Services\Setup\BranchSetupService;
use App\Services\Setup\PhoneSetupService;
use App\Services\Setup\CalcomSetupService;
use App\Services\Setup\ServiceTemplateService;
use App\Services\Setup\StaffSetupService;
use App\Services\Setup\RetellSetupService;

class SetupOrchestrator
{
    protected CompanySetupService $companyService;
    protected BranchSetupService $branchService;
    protected PhoneSetupService $phoneService;
    protected CalcomSetupService $calcomService;
    protected ServiceTemplateService $serviceTemplateService;
    protected StaffSetupService $staffService;
    protected RetellSetupService $retellService;
    protected array $progressCallbacks = [];

    public function __construct()
    {
        $this->companyService = new CompanySetupService();
        $this->branchService = new BranchSetupService();
        $this->phoneService = new PhoneSetupService();
        $this->calcomService = new CalcomSetupService();
        $this->serviceTemplateService = new ServiceTemplateService();
        $this->staffService = new StaffSetupService();
        $this->retellService = new RetellSetupService();
    }

    /**
     * Execute the complete setup process
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $this->reportProgress(10, 'Firma wird erstellt...');
            
            // Step 1: Create or update company
            $company = $this->setupCompany($data);
            
            $this->reportProgress(20, 'Filialen werden eingerichtet...');
            
            // Step 2: Setup branches
            $branches = $this->setupBranches($company, $data);
            
            $this->reportProgress(30, 'Telefonnummern werden konfiguriert...');
            
            // Step 3: Configure phone numbers
            $this->setupPhoneNumbers($branches, $data);
            
            $this->reportProgress(40, 'Cal.com Integration wird eingerichtet...');
            
            // Step 4: Setup Cal.com integration
            $this->setupCalcomIntegration($company, $branches, $data);
            
            $this->reportProgress(60, 'Dienstleistungen werden erstellt...');
            
            // Step 5: Create services from templates
            $services = $this->setupServices($company, $data);
            
            $this->reportProgress(70, 'Mitarbeiter werden angelegt...');
            
            // Step 6: Setup staff
            $staff = $this->setupStaff($company, $branches, $services, $data);
            
            $this->reportProgress(85, 'KI-Agent wird konfiguriert...');
            
            // Step 7: Provision AI agent
            $this->setupRetellAgent($company, $branches, $data);
            
            $this->reportProgress(95, 'Setup wird abgeschlossen...');
            
            // Final: Mark setup complete
            $this->finalizeSetup($company);
            
            Log::info('Setup completed successfully', [
                'company_id' => $company->id,
                'branches_count' => count($branches),
                'services_count' => count($services),
                'staff_count' => count($staff)
            ]);
            
            return [
                'success' => true,
                'company' => $company,
                'branches' => $branches,
                'services' => $services,
                'staff' => $staff
            ];
        });
    }

    /**
     * Set progress callback
     */
    public function onProgress(callable $callback): self
    {
        $this->progressCallbacks[] = $callback;
        return $this;
    }

    /**
     * Report progress
     */
    protected function reportProgress(int $percentage, string $message): void
    {
        foreach ($this->progressCallbacks as $callback) {
            $callback($percentage, $message);
        }
    }

    /**
     * Setup company
     */
    protected function setupCompany(array $data): Company
    {
        if (isset($data['company_id']) && $data['company_id']) {
            // Update existing company
            $company = Company::findOrFail($data['company_id']);
            $company->update([
                'name' => $data['company_name'],
                'industry' => $data['industry'],
                'email' => $data['company_email'] ?? null,
                'website' => $data['company_website'] ?? null,
                'description' => $data['company_description'] ?? null,
            ]);
            return $company;
        }
        
        // Create new company
        return $this->companyService->createCompany($data);
    }

    /**
     * Setup branches
     */
    protected function setupBranches(Company $company, array $data): array
    {
        if (isset($data['branches']) && !empty($data['branches'])) {
            return $this->branchService->createBranches($company, $data['branches']);
        }
        
        // Legacy single branch support
        $branchData = [
            'name' => $data['branch_name'] ?? 'Hauptfiliale',
            'city' => $data['branch_city'] ?? null,
            'address' => $data['branch_address'] ?? null,
            'phone_number' => $data['branch_phone'] ?? null,
        ];
        
        return $this->branchService->createBranches($company, [$branchData]);
    }

    /**
     * Setup phone numbers
     */
    protected function setupPhoneNumbers(array $branches, array $data): void
    {
        foreach ($branches as $branch) {
            if ($branch->phone_number) {
                $this->phoneService->provisionPhoneNumber($branch);
            }
        }
    }

    /**
     * Setup Cal.com integration
     */
    protected function setupCalcomIntegration(Company $company, array $branches, array $data): void
    {
        if ($data['calcom_api_key'] ?? null) {
            $this->calcomService->setupIntegration($company, $data['calcom_api_key']);
            
            // Link event types to branches
            if ($data['calcom_event_type_id'] ?? null) {
                foreach ($branches as $branch) {
                    $this->calcomService->linkEventTypeToBranch($branch, $data['calcom_event_type_id']);
                }
            }
        }
    }

    /**
     * Setup services from templates
     */
    protected function setupServices(Company $company, array $data): array
    {
        return $this->serviceTemplateService->createFromIndustryTemplate(
            $company,
            $data['industry']
        );
    }

    /**
     * Setup staff
     */
    protected function setupStaff(Company $company, array $branches, array $services, array $data): array
    {
        $staffData = [
            'name' => $data['default_staff_name'] ?? 'Standard Mitarbeiter',
            'email' => $data['default_staff_email'] ?? null,
        ];
        
        return $this->staffService->createDefaultStaff(
            $company,
            $branches,
            $services,
            $staffData
        );
    }

    /**
     * Setup Retell AI agent
     */
    protected function setupRetellAgent(Company $company, array $branches, array $data): void
    {
        if ($data['retell_api_key'] ?? null) {
            $this->retellService->provisionAgent($company, $branches, [
                'api_key' => $data['retell_api_key'],
                'agent_name' => $data['agent_name'] ?? $company->name . ' AI Assistant',
                'industry' => $data['industry'],
            ]);
        }
    }

    /**
     * Finalize setup
     */
    protected function finalizeSetup(Company $company): void
    {
        $company->update([
            'settings' => array_merge($company->settings ?? [], [
                'wizard_completed' => true,
                'wizard_completed_at' => now()->toISOString(),
                'setup_version' => '2.0',
            ])
        ]);
    }
}