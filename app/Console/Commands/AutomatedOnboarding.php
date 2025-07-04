<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Staff;
use App\Models\Service;
use App\Models\User;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\Provisioning\RetellAgentProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutomatedOnboarding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'askproai:onboard 
                            {--company= : Name des Unternehmens}
                            {--industry=beauty : Branche (medical, beauty, handwerk, legal)}
                            {--phone= : Haupttelefonnummer}
                            {--calcom-key= : Cal.com API Key}
                            {--retell-key= : Retell.ai API Key}
                            {--admin-email= : Admin E-Mail Adresse}
                            {--quick : Schnellsetup mit Standardwerten}
                            {--test : Test-Modus ohne externe API-Calls}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatisiertes Onboarding für neue Unternehmen';

    /**
     * Industry templates für verschiedene Branchen
     */
    protected array $industryTemplates = [
        'medical' => [
            'services' => [
                ['name' => 'Erstberatung', 'duration' => 30, 'price' => 0],
                ['name' => 'Untersuchung', 'duration' => 45, 'price' => 80],
                ['name' => 'Behandlung', 'duration' => 30, 'price' => 120],
                ['name' => 'Nachuntersuchung', 'duration' => 20, 'price' => 60],
            ],
            'greeting' => 'Guten Tag, Sie sind verbunden mit der Praxis {company}. Wie kann ich Ihnen helfen?',
            'working_hours' => [
                'monday' => ['09:00', '18:00'],
                'tuesday' => ['09:00', '18:00'],
                'wednesday' => ['09:00', '13:00'],
                'thursday' => ['09:00', '18:00'],
                'friday' => ['09:00', '16:00'],
            ],
        ],
        'beauty' => [
            'services' => [
                ['name' => 'Haarschnitt Damen', 'duration' => 45, 'price' => 45],
                ['name' => 'Haarschnitt Herren', 'duration' => 30, 'price' => 25],
                ['name' => 'Färben', 'duration' => 90, 'price' => 80],
                ['name' => 'Styling', 'duration' => 30, 'price' => 35],
                ['name' => 'Dauerwelle', 'duration' => 120, 'price' => 120],
            ],
            'greeting' => 'Willkommen bei {company}! Möchten Sie einen Termin vereinbaren?',
            'working_hours' => [
                'monday' => ['closed', 'closed'],
                'tuesday' => ['09:00', '19:00'],
                'wednesday' => ['09:00', '19:00'],
                'thursday' => ['09:00', '20:00'],
                'friday' => ['09:00', '19:00'],
                'saturday' => ['08:00', '16:00'],
            ],
        ],
        'handwerk' => [
            'services' => [
                ['name' => 'Beratung vor Ort', 'duration' => 60, 'price' => 0],
                ['name' => 'Kleinreparatur', 'duration' => 60, 'price' => 80],
                ['name' => 'Installation', 'duration' => 120, 'price' => 150],
                ['name' => 'Wartung', 'duration' => 90, 'price' => 120],
            ],
            'greeting' => 'Guten Tag, {company} am Apparat. Wie kann ich Ihnen behilflich sein?',
            'working_hours' => [
                'monday' => ['07:00', '17:00'],
                'tuesday' => ['07:00', '17:00'],
                'wednesday' => ['07:00', '17:00'],
                'thursday' => ['07:00', '17:00'],
                'friday' => ['07:00', '15:00'],
            ],
        ],
        'legal' => [
            'services' => [
                ['name' => 'Erstberatung', 'duration' => 60, 'price' => 150],
                ['name' => 'Vertragsberatung', 'duration' => 90, 'price' => 250],
                ['name' => 'Gerichtstermin Vorbereitung', 'duration' => 120, 'price' => 350],
                ['name' => 'Mediation', 'duration' => 90, 'price' => 200],
            ],
            'greeting' => 'Kanzlei {company}, guten Tag. Wie darf ich Ihnen weiterhelfen?',
            'working_hours' => [
                'monday' => ['09:00', '17:00'],
                'tuesday' => ['09:00', '17:00'],
                'wednesday' => ['09:00', '17:00'],
                'thursday' => ['09:00', '17:00'],
                'friday' => ['09:00', '14:00'],
            ],
        ],
    ];

    protected CalcomV2Service $calcomService;
    protected RetellV2Service $retellService;
    protected RetellAgentProvisioner $agentProvisioner;

    public function __construct(
        CalcomV2Service $calcomService,
        RetellV2Service $retellService,
        RetellAgentProvisioner $agentProvisioner
    ) {
        parent::__construct();
        $this->calcomService = $calcomService;
        $this->retellService = $retellService;
        $this->agentProvisioner = $agentProvisioner;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 AskProAI Automatisiertes Onboarding');
        $this->line('=====================================');

        // Gather information
        $data = $this->gatherOnboardingData();

        if (!$this->option('no-interaction')) {
            if (!$this->confirm('Möchten Sie mit dem Onboarding fortfahren?')) {
                $this->warn('Onboarding abgebrochen.');
                return 0;
            }
        }

        $this->line('');
        $progressBar = $this->output->createProgressBar(10);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        try {
            DB::beginTransaction();

            // Step 1: Create Company
            $progressBar->setMessage('Erstelle Unternehmen...');
            $progressBar->start();
            $company = $this->createCompany($data);
            $progressBar->advance();

            // Step 2: Create Branch
            $progressBar->setMessage('Erstelle Filiale...');
            $branch = $this->createBranch($company, $data);
            $progressBar->advance();

            // Step 3: Create Admin User
            $progressBar->setMessage('Erstelle Admin-Benutzer...');
            $adminUser = $this->createAdminUser($company, $data);
            $progressBar->advance();

            // Step 4: Configure Phone Number
            $progressBar->setMessage('Konfiguriere Telefonnummer...');
            $phoneNumber = $this->configurePhoneNumber($company, $branch, $data);
            $progressBar->advance();

            // Step 5: Configure Cal.com
            $progressBar->setMessage('Konfiguriere Cal.com Integration...');
            if (!$this->option('test')) {
                $this->configureCalcom($company, $data);
            }
            $progressBar->advance();

            // Step 6: Configure Retell.ai
            $progressBar->setMessage('Konfiguriere Retell.ai Agent...');
            $agent = null;
            if (!$this->option('test')) {
                $agent = $this->configureRetell($company, $branch, $data);
            }
            $progressBar->advance();

            // Step 7: Create Services
            $progressBar->setMessage('Erstelle Dienstleistungen...');
            $services = $this->createServices($branch, $data['industry']);
            $progressBar->advance();

            // Step 8: Create Staff
            $progressBar->setMessage('Erstelle Mitarbeiter...');
            $staff = $this->createStaff($branch, $data);
            $progressBar->advance();

            // Step 9: Link Services to Staff
            $progressBar->setMessage('Verknüpfe Dienstleistungen mit Mitarbeitern...');
            $this->linkServicesToStaff($staff, $services);
            $progressBar->advance();

            // Step 10: Run Tests
            $progressBar->setMessage('Führe Tests durch...');
            if (!$this->option('test')) {
                $testResults = $this->runIntegrationTests($company, $branch, $agent);
            }
            $progressBar->advance();

            DB::commit();
            $progressBar->finish();

            $this->displaySuccessSummary($company, $branch, $adminUser, $data);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $progressBar->finish();
            
            $this->error("\n\n❌ Fehler beim Onboarding: " . $e->getMessage());
            Log::error('Onboarding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Gather all required onboarding data
     */
    protected function gatherOnboardingData(): array
    {
        if ($this->option('quick')) {
            return $this->getQuickSetupData();
        }

        $data = [];

        // Company Name
        $data['company_name'] = $this->option('company') 
            ?? $this->ask('Wie lautet der Name des Unternehmens?');

        // Industry
        $industries = array_keys($this->industryTemplates);
        $data['industry'] = $this->option('industry');
        if (!in_array($data['industry'], $industries)) {
            $data['industry'] = $this->choice(
                'Welche Branche?',
                $industries,
                array_search('beauty', $industries)
            );
        }

        // Phone Number
        $data['phone'] = $this->option('phone') 
            ?? $this->ask('Haupttelefonnummer (mit Ländervorwahl, z.B. +49 30 12345678)');

        // Admin Email
        $data['admin_email'] = $this->option('admin-email') 
            ?? $this->ask('Admin E-Mail Adresse');

        // Cal.com API Key
        $data['calcom_key'] = $this->option('calcom-key') 
            ?? $this->ask('Cal.com API Key (optional - Enter für Standard)', config('services.calcom.api_key'));

        // Retell API Key
        $data['retell_key'] = $this->option('retell-key') 
            ?? $this->ask('Retell.ai API Key (optional - Enter für Standard)', config('services.retell.api_key'));

        // Branch Details
        $data['branch_name'] = $this->ask('Name der ersten Filiale', 'Hauptfiliale');
        $data['branch_city'] = $this->ask('Stadt', 'Berlin');
        $data['branch_address'] = $this->ask('Adresse', 'Musterstraße 1, 10115 Berlin');
        $data['branch_postal_code'] = $this->ask('Postleitzahl', '10115');

        return $data;
    }

    /**
     * Get quick setup data with defaults
     */
    protected function getQuickSetupData(): array
    {
        $companyName = $this->option('company') ?? 'Demo ' . Str::random(4);
        
        return [
            'company_name' => $companyName,
            'industry' => $this->option('industry') ?? 'beauty',
            'phone' => $this->option('phone') ?? '+49 30 ' . rand(10000000, 99999999),
            'admin_email' => $this->option('admin-email') ?? 'admin@' . Str::slug($companyName) . '.de',
            'calcom_key' => $this->option('calcom-key') ?? config('services.calcom.api_key'),
            'retell_key' => $this->option('retell-key') ?? config('services.retell.api_key'),
            'branch_name' => 'Hauptfiliale',
            'branch_city' => 'Berlin',
            'branch_address' => 'Musterstraße 1, 10115 Berlin',
            'branch_postal_code' => '10115',
        ];
    }

    /**
     * Create the company
     */
    protected function createCompany(array $data): Company
    {
        $company = Company::create([
            'name' => $data['company_name'],
            'subscription_status' => 'trial',
            'subscription_ends_at' => now()->addDays(14),
            'calcom_api_key' => encrypt($data['calcom_key']),
            'retell_api_key' => encrypt($data['retell_key']),
            'settings' => [
                'industry' => $data['industry'],
                'timezone' => 'Europe/Berlin',
                'language' => 'de',
                'currency' => 'EUR',
            ],
        ]);

        $this->info("\n✅ Unternehmen erstellt: {$company->name}");

        return $company;
    }

    /**
     * Create the branch
     */
    protected function createBranch(Company $company, array $data): Branch
    {
        $workingHours = $this->industryTemplates[$data['industry']]['working_hours'] ?? [];
        
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => $data['branch_name'],
            'phone' => $data['phone'],
            'email' => 'info@' . Str::slug($company->name) . '.de',
            'address' => $data['branch_address'],
            'city' => $data['branch_city'],
            'postal_code' => $data['branch_postal_code'],
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'is_active' => true,
            'working_hours' => $workingHours,
        ]);

        $this->info("✅ Filiale erstellt: {$branch->name}");

        return $branch;
    }

    /**
     * Create admin user
     */
    protected function createAdminUser(Company $company, array $data): User
    {
        $password = Str::random(12);
        
        $user = User::create([
            'name' => 'Admin ' . $company->name,
            'email' => $data['admin_email'],
            'password' => bcrypt($password),
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user->assignRole('company_admin');

        $this->info("✅ Admin-Benutzer erstellt: {$user->email}");
        $this->warn("⚠️  Passwort: $password (Bitte notieren und sicher aufbewahren!)");

        return $user;
    }

    /**
     * Configure phone number
     */
    protected function configurePhoneNumber(Company $company, Branch $branch, array $data): PhoneNumber
    {
        $phoneNumber = PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'number' => $data['phone'],
            'type' => 'direct',
            'is_active' => true,
            'is_primary' => true,
            'capabilities' => ['voice', 'sms'],
        ]);

        $this->info("✅ Telefonnummer konfiguriert: {$phoneNumber->number}");

        return $phoneNumber;
    }

    /**
     * Configure Cal.com integration
     */
    protected function configureCalcom(Company $company, array $data): void
    {
        try {
            // Test API connection
            $this->calcomService->setApiKey($data['calcom_key']);
            $user = $this->calcomService->getMe();
            
            $company->settings = array_merge($company->settings ?? [], [
                'calcom_user_id' => $user['id'] ?? null,
                'calcom_team_slug' => $user['teams'][0]['slug'] ?? null,
            ]);
            $company->save();

            $this->info("✅ Cal.com Integration konfiguriert");
        } catch (\Exception $e) {
            $this->warn("⚠️  Cal.com Integration konnte nicht konfiguriert werden: " . $e->getMessage());
        }
    }

    /**
     * Configure Retell.ai agent
     */
    protected function configureRetell(Company $company, Branch $branch, array $data): ?RetellAgent
    {
        try {
            $greeting = str_replace('{company}', $company->name, 
                $this->industryTemplates[$data['industry']]['greeting']);

            // Use agent provisioner to create agent
            $agent = $this->agentProvisioner->provisionAgent($branch, [
                'agent_name' => $company->name . ' - ' . $branch->name,
                'voice_id' => '11labs-Adrian',
                'language' => 'de',
                'greeting_message' => $greeting,
            ]);

            $this->info("✅ Retell.ai Agent konfiguriert: {$agent->retell_agent_id}");

            return $agent;
        } catch (\Exception $e) {
            $this->warn("⚠️  Retell.ai Agent konnte nicht konfiguriert werden: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create services based on industry template
     */
    protected function createServices(Branch $branch, string $industry): array
    {
        $services = [];
        $serviceTemplates = $this->industryTemplates[$industry]['services'] ?? [];

        foreach ($serviceTemplates as $template) {
            $service = Service::create([
                'company_id' => $branch->company_id,
                // Don't set branch_id due to type mismatch (UUID vs bigint)
                'name' => $template['name'],
                'default_duration_minutes' => $template['duration'],
                'duration' => $template['duration'],
                'price' => $template['price'] ?? 0,
                'active' => true,
                'is_online_bookable' => true,
                'description' => 'Automatisch erstellt für ' . $industry,
            ]);
            $services[] = $service;
        }

        $this->info("✅ " . count($services) . " Dienstleistungen erstellt");

        return $services;
    }

    /**
     * Create staff members
     */
    protected function createStaff(Branch $branch, array $data): array
    {
        $staffMembers = [];
        
        // Create 2 default staff members
        $staffData = [
            ['name' => 'Maria Muster', 'email' => 'maria@' . Str::slug($data['company_name']) . '.de'],
            ['name' => 'Max Beispiel', 'email' => 'max@' . Str::slug($data['company_name']) . '.de'],
        ];

        foreach ($staffData as $staff) {
            $member = Staff::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'name' => $staff['name'],
                'email' => $staff['email'],
                'active' => true,
                'is_bookable' => true,
            ]);
            $staffMembers[] = $member;
        }

        $this->info("✅ " . count($staffMembers) . " Mitarbeiter erstellt");

        return $staffMembers;
    }

    /**
     * Link services to staff
     */
    protected function linkServicesToStaff(array $staff, array $services): void
    {
        foreach ($staff as $member) {
            // Assign all services to each staff member
            $serviceIds = collect($services)->pluck('id')->toArray();
            $member->services()->sync($serviceIds);
        }

        $this->info("✅ Dienstleistungen mit Mitarbeitern verknüpft");
    }

    /**
     * Run integration tests
     */
    protected function runIntegrationTests(Company $company, Branch $branch, ?RetellAgent $agent): array
    {
        $results = [
            'calcom' => false,
            'retell' => false,
        ];

        // Test Cal.com
        try {
            $this->calcomService->setApiKey($company->calcom_api_key);
            $this->calcomService->getMe();
            $results['calcom'] = true;
            $this->info("✅ Cal.com Integration Test erfolgreich");
        } catch (\Exception $e) {
            $this->warn("❌ Cal.com Integration Test fehlgeschlagen");
        }

        // Test Retell
        if ($agent) {
            try {
                $this->retellService->setApiKey($company->retell_api_key);
                $agentData = $this->retellService->getAgent($agent->retell_agent_id);
                $results['retell'] = !empty($agentData);
                $this->info("✅ Retell.ai Integration Test erfolgreich");
            } catch (\Exception $e) {
                $this->warn("❌ Retell.ai Integration Test fehlgeschlagen");
            }
        }

        return $results;
    }

    /**
     * Display success summary
     */
    protected function displaySuccessSummary(Company $company, Branch $branch, User $adminUser, array $data): void
    {
        $this->newLine(2);
        $this->info('🎉 Onboarding erfolgreich abgeschlossen!');
        $this->line('=====================================');
        
        $this->table(
            ['Eigenschaft', 'Wert'],
            [
                ['Unternehmen', $company->name],
                ['Filiale', $branch->name],
                ['Telefonnummer', $branch->phone],
                ['Admin E-Mail', $adminUser->email],
                ['Branche', $data['industry']],
                ['Trial endet', $company->subscription_ends_at ? $company->subscription_ends_at->format('d.m.Y') : 'N/A'],
            ]
        );

        $this->newLine();
        $this->info('🔗 Nächste Schritte:');
        $this->line('1. Login unter: https://api.askproai.de/admin');
        $this->line('2. Zugangsdaten: ' . $adminUser->email);
        $this->line('3. Dashboard aufrufen und Konfiguration prüfen');
        $this->line('4. Testanruf durchführen');
        
        if ($this->option('test')) {
            $this->newLine();
            $this->warn('⚠️  Test-Modus: Externe API-Integrationen wurden übersprungen');
        }
    }
}