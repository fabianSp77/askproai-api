<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\Company;
use App\Services\ServiceMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssignServicesToCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:assign
                            {--auto : Automatically assign services with high confidence}
                            {--interactive : Interactive mode with suggestions}
                            {--dry-run : Show what would be assigned without making changes}
                            {--min-confidence=80 : Minimum confidence for auto-assignment (0-100)}
                            {--service= : Assign specific service by ID}
                            {--unassigned : Only process unassigned services}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Intelligently assign services to companies based on name matching';

    private ServiceMatcher $matcher;

    public function __construct(ServiceMatcher $matcher)
    {
        parent::__construct();
        $this->matcher = $matcher;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('========================================');
        $this->info(' Service to Company Assignment Tool');
        $this->info('========================================');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Determine which mode to run
        if ($this->option('interactive')) {
            return $this->handleInteractiveMode();
        } elseif ($this->option('auto')) {
            return $this->handleAutoMode();
        } else {
            return $this->handleAnalysisMode();
        }
    }

    /**
     * Handle analysis mode - show suggestions without making changes
     */
    private function handleAnalysisMode(): int
    {
        $this->info("\nAnalyzing services for company assignment...\n");

        $services = $this->getServicesToProcess();

        if ($services->isEmpty()) {
            $this->warn('No services to process');
            return Command::SUCCESS;
        }

        $this->info("Found {$services->count()} services to analyze\n");

        $stats = [
            'high_confidence' => 0,
            'medium_confidence' => 0,
            'low_confidence' => 0,
            'no_match' => 0
        ];

        foreach ($services as $service) {
            $this->line("Service: <info>{$service->name}</info>");

            if ($service->company_id) {
                $this->line("  Current: {$service->company->name}");
            }

            $suggestions = $this->matcher->suggestCompanies($service);

            if ($suggestions->isEmpty()) {
                $this->warn("  No matches found");
                $stats['no_match']++;
            } else {
                $this->line("  Suggestions:");
                foreach ($suggestions->take(3) as $index => $suggestion) {
                    $confidence = $suggestion['confidence'];
                    $color = $this->getConfidenceColor($confidence);

                    $this->line(sprintf(
                        "    %d. <$color>%s (%.1f%%)</$color>",
                        $index + 1,
                        $suggestion['company']->name,
                        $confidence
                    ));

                    if (!empty($suggestion['matched_keywords'])) {
                        $this->line("       Keywords: " . implode(', ', $suggestion['matched_keywords']));
                    }

                    // Update stats
                    if ($index === 0) {
                        if ($confidence >= 80) $stats['high_confidence']++;
                        elseif ($confidence >= 50) $stats['medium_confidence']++;
                        else $stats['low_confidence']++;
                    }
                }
            }
            $this->line('');
        }

        // Display summary
        $this->displaySummary($stats);

        return Command::SUCCESS;
    }

    /**
     * Handle automatic assignment mode
     */
    private function handleAutoMode(): int
    {
        $minConfidence = (float) $this->option('min-confidence');

        $this->info("\nAuto-assigning services with confidence >= {$minConfidence}%\n");

        if ($this->option('dry-run')) {
            $this->info("Simulating auto-assignment...\n");
        }

        $services = $this->getServicesToProcess();

        $assigned = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($services->count());
        $progressBar->start();

        foreach ($services as $service) {
            $progressBar->advance();

            if ($this->option('dry-run')) {
                $suggestions = $this->matcher->suggestCompanies($service);

                if ($suggestions->isNotEmpty() && $suggestions->first()['confidence'] >= $minConfidence) {
                    $assigned++;
                } else {
                    $skipped++;
                }
            } else {
                $company = $this->matcher->autoAssign($service, $minConfidence);

                if ($company) {
                    $assigned++;
                } else {
                    $skipped++;
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('Auto-assignment complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Services Processed', $services->count()],
                ['Assigned', $assigned],
                ['Skipped (Low Confidence)', $skipped],
                ['Failed', $failed],
            ]
        );

        if (!$this->option('dry-run') && $assigned > 0) {
            $this->info("\n✅ Successfully assigned {$assigned} services to companies");

            // Log the assignment
            Log::info('[Service Assignment] Auto-assignment completed', [
                'assigned' => $assigned,
                'skipped' => $skipped,
                'min_confidence' => $minConfidence
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Handle interactive assignment mode
     */
    private function handleInteractiveMode(): int
    {
        $this->info("\nInteractive assignment mode\n");

        $services = $this->getServicesToProcess();

        if ($services->isEmpty()) {
            $this->warn('No services to process');
            return Command::SUCCESS;
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($services as $service) {
            $this->newLine();
            $this->info("Service: {$service->name}");

            if ($service->description) {
                $this->line("Description: {$service->description}");
            }

            if ($service->company_id) {
                $this->line("Currently assigned to: {$service->company->name}");
            }

            $suggestions = $this->matcher->suggestCompanies($service);

            if ($suggestions->isEmpty()) {
                $this->warn("No suggestions available");

                if ($this->confirm('Would you like to manually select a company?')) {
                    $company = $this->selectCompany();
                    if ($company && !$this->option('dry-run')) {
                        $this->assignManually($service, $company);
                        $assigned++;
                    }
                } else {
                    $skipped++;
                }
            } else {
                $this->line("\nSuggested companies:");

                $choices = ['Skip'];
                foreach ($suggestions->take(5) as $index => $suggestion) {
                    $confidence = $suggestion['confidence'];
                    $color = $this->getConfidenceColor($confidence);

                    $choiceText = sprintf(
                        "%s (%.1f%% confidence)",
                        $suggestion['company']->name,
                        $confidence
                    );
                    $choices[] = $choiceText;

                    $this->line(sprintf(
                        "  %d. <$color>%s</$color>",
                        $index + 1,
                        $choiceText
                    ));

                    if (!empty($suggestion['matched_keywords'])) {
                        $this->line("     Keywords: " . implode(', ', $suggestion['matched_keywords']));
                    }
                }

                $choices[] = 'Manual selection';

                $choice = $this->choice(
                    'Select a company to assign',
                    $choices,
                    0
                );

                if ($choice === 'Skip') {
                    $skipped++;
                } elseif ($choice === 'Manual selection') {
                    $company = $this->selectCompany();
                    if ($company && !$this->option('dry-run')) {
                        $this->assignManually($service, $company);
                        $assigned++;
                    }
                } else {
                    // Find the selected suggestion
                    $selectedIndex = array_search($choice, $choices) - 1; // -1 for 'Skip'
                    if (isset($suggestions[$selectedIndex]) && !$this->option('dry-run')) {
                        $suggestion = $suggestions[$selectedIndex];
                        $this->assignWithSuggestion($service, $suggestion);
                        $assigned++;
                    }
                }
            }

            if (!$this->confirm('Continue with next service?', true)) {
                break;
            }
        }

        $this->newLine();
        $this->info('Interactive assignment complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Assigned', $assigned],
                ['Skipped', $skipped],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Get services to process based on options
     */
    private function getServicesToProcess()
    {
        $query = Service::with('company');

        if ($this->option('service')) {
            $query->where('id', $this->option('service'));
        } elseif ($this->option('unassigned')) {
            $query->whereNull('company_id')
                ->orWhereNull('assignment_method');
        }

        // Always include Cal.com synced services
        $query->whereNotNull('calcom_event_type_id');

        return $query->orderBy('name')->get();
    }

    /**
     * Select a company manually
     */
    private function selectCompany(): ?Company
    {
        $companies = Company::orderBy('name')->pluck('name', 'id')->toArray();

        $choices = ['Cancel'] + $companies;

        $choice = $this->choice(
            'Select a company',
            array_values($choices),
            0
        );

        if ($choice === 'Cancel') {
            return null;
        }

        $companyId = array_search($choice, $choices);
        return Company::find($companyId);
    }

    /**
     * Assign service to company manually
     */
    private function assignManually(Service $service, Company $company): void
    {
        $service->update([
            'company_id' => $company->id,
            'assignment_method' => 'manual',
            'assignment_confidence' => null,
            'assignment_notes' => 'Manually assigned via CLI',
            'assignment_date' => now(),
            'assigned_by' => auth()->id()
        ]);

        $this->info("✅ Assigned '{$service->name}' to '{$company->name}'");
    }

    /**
     * Assign service based on suggestion
     */
    private function assignWithSuggestion(Service $service, array $suggestion): void
    {
        $service->update([
            'company_id' => $suggestion['company']->id,
            'assignment_method' => 'suggested',
            'assignment_confidence' => $suggestion['confidence'],
            'assignment_notes' => $suggestion['reasoning'],
            'assignment_date' => now(),
            'assigned_by' => auth()->id()
        ]);

        $this->info("✅ Assigned '{$service->name}' to '{$suggestion['company']->name}' ({$suggestion['confidence']}% confidence)");
    }

    /**
     * Get color based on confidence level
     */
    private function getConfidenceColor(float $confidence): string
    {
        if ($confidence >= 80) return 'info';
        if ($confidence >= 60) return 'comment';
        if ($confidence >= 40) return 'warn';
        return 'error';
    }

    /**
     * Display summary statistics
     */
    private function displaySummary(array $stats): void
    {
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Confidence Level', 'Count'],
            [
                ['High (≥80%)', $stats['high_confidence']],
                ['Medium (50-79%)', $stats['medium_confidence']],
                ['Low (<50%)', $stats['low_confidence']],
                ['No Match', $stats['no_match']],
            ]
        );

        $total = array_sum($stats);
        $assignable = $stats['high_confidence'];

        $this->newLine();
        $this->info("Total services analyzed: {$total}");
        $this->info("Services ready for auto-assignment (≥80% confidence): {$assignable}");

        if ($assignable > 0) {
            $this->newLine();
            $this->comment("Run 'php artisan services:assign --auto' to automatically assign high-confidence matches");
        }
    }
}