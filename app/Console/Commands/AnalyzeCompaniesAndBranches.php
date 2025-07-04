<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeCompaniesAndBranches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:companies 
                            {--fix : Automatische Fixes anwenden}
                            {--delete-inactive : Inaktive Companies löschen}
                            {--export : Ergebnisse als CSV exportieren}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analysiert alle Companies und Branches auf Vollständigkeit und Probleme';

    protected array $issues = [];
    protected array $recommendations = [];
    protected array $deleteCandiates = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 AskProAI Company & Branch Analyzer');
        $this->line('=====================================');

        // Disable tenant scope for admin analysis
        $companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with([
                'branches' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class)
                        ->with([
                            'staff' => function ($q) {
                                $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
                            },
                            'services' => function ($q) {
                                $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
                            },
                            'phoneNumbers' => function ($q) {
                                $q->withoutGlobalScope(\App\Scopes\TenantScope::class)
                                    ->with(['retellAgent' => function ($r) {
                                        $r->withoutGlobalScope(\App\Scopes\TenantScope::class);
                                    }]);
                            }
                        ]);
                },
                'appointments' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
                },
                'calls' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
                }
            ])->get();

        $this->info("\n📊 Analysiere {$companies->count()} Unternehmen...\n");

        $progressBar = $this->output->createProgressBar($companies->count());

        foreach ($companies as $company) {
            $this->analyzeCompany($company);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Zeige Ergebnisse
        $this->displayResults();

        // Fixes anwenden wenn gewünscht
        if ($this->option('fix')) {
            $this->applyFixes();
        }

        // Inaktive löschen wenn gewünscht
        if ($this->option('delete-inactive')) {
            $this->deleteInactiveCompanies();
        }

        // Export wenn gewünscht
        if ($this->option('export')) {
            $this->exportResults();
        }

        return 0;
    }

    /**
     * Analysiere einzelne Company
     */
    protected function analyzeCompany(Company $company): void
    {
        $companyIssues = [];

        // 1. Basis-Checks
        if (empty($company->name)) {
            $companyIssues[] = 'Kein Name gesetzt';
        }

        if (empty($company->email)) {
            $companyIssues[] = 'Keine Email-Adresse';
        }

        if (empty($company->timezone)) {
            $companyIssues[] = 'Keine Timezone gesetzt';
        }

        // 2. API Keys prüfen
        if (empty($company->retell_api_key)) {
            $companyIssues[] = 'Kein Retell API Key';
        }

        if (empty($company->calcom_api_key)) {
            $companyIssues[] = 'Kein Cal.com API Key';
        }

        // 3. Branches prüfen
        if ($company->branches->isEmpty()) {
            $companyIssues[] = 'Keine Filialen angelegt';
            $this->deleteCandiates[] = $company->id;
        } else {
            foreach ($company->branches as $branch) {
                $branchIssues = $this->analyzeBranch($branch);
                if (!empty($branchIssues)) {
                    $companyIssues[] = "Branch '{$branch->name}': " . implode(', ', $branchIssues);
                }
            }
        }

        // 4. Aktivität prüfen
        $lastCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $company->id)
            ->latest()
            ->first();
        $lastAppointment = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $company->id)
            ->latest()
            ->first();
        
        $daysSinceLastCall = $lastCall ? now()->diffInDays($lastCall->created_at) : 999;
        $daysSinceLastAppointment = $lastAppointment ? now()->diffInDays($lastAppointment->created_at) : 999;

        if ($daysSinceLastCall > 30 && $daysSinceLastAppointment > 30) {
            $companyIssues[] = 'Keine Aktivität seit 30+ Tagen';
            if ($company->subscription_status === 'trial') {
                $this->deleteCandiates[] = $company->id;
            }
        }

        // 5. Subscription Status
        if (!in_array($company->subscription_status, ['active', 'trial'])) {
            $companyIssues[] = "Ungültiger Subscription Status: {$company->subscription_status}";
        }

        // Speichere Issues
        if (!empty($companyIssues)) {
            $this->issues[$company->id] = [
                'name' => $company->name,
                'issues' => $companyIssues,
                'branches' => $company->branches->count(),
                'last_activity' => min($daysSinceLastCall, $daysSinceLastAppointment),
                'subscription' => $company->subscription_status,
            ];
        }

        // Generiere Empfehlungen
        $this->generateRecommendations($company, $companyIssues);
    }

    /**
     * Analysiere einzelne Branch
     */
    protected function analyzeBranch(Branch $branch): array
    {
        $issues = [];

        // Working Hours
        if (empty($branch->working_hours) || $branch->working_hours === '[]') {
            $issues[] = 'Keine Arbeitszeiten';
        }

        // Staff
        if ($branch->staff->isEmpty()) {
            $issues[] = 'Keine Mitarbeiter';
        } else if ($branch->staff->count() < 2) {
            $issues[] = 'Nur ' . $branch->staff->count() . ' Mitarbeiter';
        }

        // Services
        if ($branch->services->isEmpty()) {
            $issues[] = 'Keine Services';
        }

        // Phone Numbers
        $activePhones = $branch->phoneNumbers->where('is_active', true);
        if ($activePhones->isEmpty()) {
            $issues[] = 'Keine aktive Telefonnummer';
        } else {
            // Check Retell Agents
            foreach ($activePhones as $phone) {
                if (!$phone->retellAgent || !$phone->retellAgent->is_active) {
                    $issues[] = "Telefon {$phone->number} ohne aktiven Retell Agent";
                }
            }
        }

        // Address
        if (empty($branch->address)) {
            $issues[] = 'Keine Adresse';
        }

        return $issues;
    }

    /**
     * Generiere Empfehlungen
     */
    protected function generateRecommendations(Company $company, array $issues): void
    {
        $recommendations = [];

        foreach ($issues as $issue) {
            if (str_contains($issue, 'Retell API Key')) {
                $recommendations[] = "Retell API Key aus .env übernehmen oder manuell setzen";
            }
            
            if (str_contains($issue, 'Cal.com API Key')) {
                $recommendations[] = "Cal.com Integration über Quick Setup Wizard durchführen";
            }
            
            if (str_contains($issue, 'Keine Filialen')) {
                $recommendations[] = "Mindestens eine Filiale anlegen oder Company löschen";
            }
            
            if (str_contains($issue, 'Arbeitszeiten')) {
                $recommendations[] = "Standard-Arbeitszeiten (Mo-Fr 9-17) setzen";
            }
            
            if (str_contains($issue, 'Mitarbeiter')) {
                $recommendations[] = "Automatisches Onboarding Command nutzen";
            }
        }

        if (!empty($recommendations)) {
            $this->recommendations[$company->id] = $recommendations;
        }
    }

    /**
     * Zeige Ergebnisse
     */
    protected function displayResults(): void
    {
        $this->info('📋 Analyse-Ergebnisse');
        $this->line('====================');

        // Statistiken
        $totalCompanies = Company::count();
        $companiesWithIssues = count($this->issues);
        $perfectCompanies = $totalCompanies - $companiesWithIssues;

        $this->table(
            ['Metrik', 'Anzahl'],
            [
                ['Gesamt Companies', $totalCompanies],
                ['Ohne Probleme', $perfectCompanies],
                ['Mit Problemen', $companiesWithIssues],
                ['Löschkandidaten', count(array_unique($this->deleteCandiates))],
            ]
        );

        // Top Issues
        if (!empty($this->issues)) {
            $this->newLine();
            $this->error('🚨 Companies mit Problemen:');
            
            foreach ($this->issues as $companyId => $data) {
                $this->newLine();
                $this->warn("📍 {$data['name']} (ID: {$companyId})");
                $this->line("   Status: {$data['subscription']} | Branches: {$data['branches']} | Letzte Aktivität: vor {$data['last_activity']} Tagen");
                
                foreach ($data['issues'] as $issue) {
                    $this->line("   ❌ {$issue}");
                }
                
                if (isset($this->recommendations[$companyId])) {
                    $this->info("   💡 Empfehlungen:");
                    foreach ($this->recommendations[$companyId] as $rec) {
                        $this->line("      → {$rec}");
                    }
                }
            }
        }

        // Löschkandidaten
        if (!empty($this->deleteCandiates)) {
            $this->newLine();
            $this->error('🗑️  Löschkandidaten:');
            
            $candidates = Company::whereIn('id', array_unique($this->deleteCandiates))->get();
            foreach ($candidates as $company) {
                $this->line("   - {$company->name} (ID: {$company->id})");
            }
        }
    }

    /**
     * Wende automatische Fixes an
     */
    protected function applyFixes(): void
    {
        $this->newLine();
        
        if (!$this->confirm('Möchten Sie automatische Fixes anwenden?')) {
            return;
        }

        $this->info('🔧 Wende Fixes an...');

        foreach ($this->issues as $companyId => $data) {
            $company = Company::find($companyId);
            
            foreach ($data['issues'] as $issue) {
                // Timezone fix
                if (str_contains($issue, 'Timezone')) {
                    $company->timezone = 'Europe/Berlin';
                    $company->save();
                    $this->line("✅ Timezone gesetzt für {$company->name}");
                }
                
                // API Keys von .env
                if (str_contains($issue, 'Retell API Key') && config('services.retell.api_key')) {
                    $company->retell_api_key = encrypt(config('services.retell.api_key'));
                    $company->save();
                    $this->line("✅ Retell API Key gesetzt für {$company->name}");
                }
                
                // Working Hours für Branches
                if (str_contains($issue, 'Arbeitszeiten')) {
                    foreach ($company->branches as $branch) {
                        if (empty($branch->working_hours)) {
                            $branch->working_hours = [
                                'monday' => ['start' => '09:00', 'end' => '17:00'],
                                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                                'friday' => ['start' => '09:00', 'end' => '17:00'],
                            ];
                            $branch->save();
                            $this->line("✅ Standard-Arbeitszeiten gesetzt für {$branch->name}");
                        }
                    }
                }
            }
        }
    }

    /**
     * Lösche inaktive Companies
     */
    protected function deleteInactiveCompanies(): void
    {
        if (empty($this->deleteCandiates)) {
            $this->info('Keine Companies zum Löschen gefunden.');
            return;
        }

        $this->newLine();
        $this->error('⚠️  ACHTUNG: Dies wird Companies unwiderruflich löschen!');
        
        if (!$this->confirm('Wirklich ' . count(array_unique($this->deleteCandiates)) . ' Companies löschen?')) {
            return;
        }

        $deletedCount = 0;
        foreach (array_unique($this->deleteCandiates) as $companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $name = $company->name;
                $company->delete();
                $this->line("🗑️  Gelöscht: {$name}");
                $deletedCount++;
            }
        }

        $this->info("\n✅ {$deletedCount} Companies gelöscht.");
    }

    /**
     * Exportiere Ergebnisse
     */
    protected function exportResults(): void
    {
        $filename = storage_path('company-analysis-' . now()->format('Y-m-d-H-i-s') . '.csv');
        
        $csv = fopen($filename, 'w');
        fputcsv($csv, ['Company ID', 'Name', 'Status', 'Branches', 'Last Activity', 'Issues']);
        
        foreach ($this->issues as $companyId => $data) {
            fputcsv($csv, [
                $companyId,
                $data['name'],
                $data['subscription'],
                $data['branches'],
                $data['last_activity'] . ' days',
                implode('; ', $data['issues'])
            ]);
        }
        
        fclose($csv);
        
        $this->info("📄 Ergebnisse exportiert nach: {$filename}");
    }
}