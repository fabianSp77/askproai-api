<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Mail\WelcomeEmail;
use App\Mail\OnboardingStepEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class OnboardingService
{
    /**
     * Onboarding steps definition
     */
    protected array $steps = [
        'welcome' => [
            'name' => 'Willkommen',
            'description' => 'Willkommen bei AskProAI',
            'weight' => 10,
            'required' => true,
            'validation' => ['company_name', 'contact_person', 'email', 'phone'],
        ],
        'branch_setup' => [
            'name' => 'Standorte einrichten',
            'description' => 'Fügen Sie Ihre Geschäftsstandorte hinzu',
            'weight' => 15,
            'required' => true,
            'validation' => ['has_branches'],
        ],
        'staff_setup' => [
            'name' => 'Mitarbeiter hinzufügen',
            'description' => 'Fügen Sie Ihre Mitarbeiter hinzu',
            'weight' => 15,
            'required' => true,
            'validation' => ['has_staff'],
        ],
        'service_setup' => [
            'name' => 'Dienstleistungen definieren',
            'description' => 'Definieren Sie Ihre angebotenen Dienstleistungen',
            'weight' => 15,
            'required' => true,
            'validation' => ['has_services'],
        ],
        'working_hours' => [
            'name' => 'Arbeitszeiten festlegen',
            'description' => 'Legen Sie Ihre Geschäftszeiten fest',
            'weight' => 10,
            'required' => true,
            'validation' => ['has_working_hours'],
        ],
        'calcom_integration' => [
            'name' => 'Cal.com Integration',
            'description' => 'Verbinden Sie Ihr Cal.com Konto',
            'weight' => 15,
            'required' => false,
            'validation' => ['calcom_connected'],
        ],
        'retell_setup' => [
            'name' => 'KI-Telefon einrichten',
            'description' => 'Konfigurieren Sie Ihren KI-Telefonagenten',
            'weight' => 10,
            'required' => false,
            'validation' => ['retell_connected'],
        ],
        'test_call' => [
            'name' => 'Testanruf durchführen',
            'description' => 'Testen Sie Ihre Konfiguration mit einem Anruf',
            'weight' => 5,
            'required' => false,
            'validation' => ['test_call_completed'],
        ],
        'completion' => [
            'name' => 'Fertigstellung',
            'description' => 'Überprüfen Sie Ihre Einstellungen',
            'weight' => 5,
            'required' => true,
            'validation' => [],
        ],
    ];

    /**
     * Get or create onboarding progress for a company/user
     */
    public function getProgress(Company $company, User $user): array
    {
        $progress = DB::table('onboarding_progress')
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$progress) {
            $progressId = DB::table('onboarding_progress')->insertGetId([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'current_step' => 'welcome',
                'completed_steps' => json_encode([]),
                'step_data' => json_encode([]),
                'progress_percentage' => 0,
                'is_completed' => false,
                'last_activity_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $progress = DB::table('onboarding_progress')->find($progressId);
        }

        return [
            'id' => $progress->id,
            'current_step' => $progress->current_step,
            'completed_steps' => json_decode($progress->completed_steps, true) ?: [],
            'step_data' => json_decode($progress->step_data, true) ?: [],
            'progress_percentage' => $progress->progress_percentage,
            'is_completed' => (bool) $progress->is_completed,
            'completed_at' => $progress->completed_at,
            'last_activity_at' => $progress->last_activity_at,
        ];
    }

    /**
     * Update onboarding progress
     */
    public function updateProgress(Company $company, User $user, string $step, array $data = []): array
    {
        $progress = $this->getProgress($company, $user);
        $completedSteps = $progress['completed_steps'];
        $stepData = $progress['step_data'];

        // Mark step as completed if not already
        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        // Store step data
        $stepData[$step] = array_merge($stepData[$step] ?? [], $data);

        // Calculate progress percentage
        $totalWeight = collect($this->steps)->sum('weight');
        $completedWeight = collect($this->steps)
            ->filter(fn($s, $key) => in_array($key, $completedSteps))
            ->sum('weight');
        $progressPercentage = round(($completedWeight / $totalWeight) * 100);

        // Determine next step
        $nextStep = $this->getNextStep($completedSteps);
        $isCompleted = $nextStep === null && $this->validateAllRequiredSteps($company);

        // Update database
        DB::table('onboarding_progress')
            ->where('id', $progress['id'])
            ->update([
                'current_step' => $nextStep ?? 'completion',
                'completed_steps' => json_encode($completedSteps),
                'step_data' => json_encode($stepData),
                'progress_percentage' => $progressPercentage,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);

        // Send progress email if milestone reached
        if ($progressPercentage >= 25 && $progressPercentage < 30) {
            $this->sendMilestoneEmail($company, $user, '25% abgeschlossen');
        } elseif ($progressPercentage >= 50 && $progressPercentage < 55) {
            $this->sendMilestoneEmail($company, $user, '50% abgeschlossen');
        } elseif ($progressPercentage >= 75 && $progressPercentage < 80) {
            $this->sendMilestoneEmail($company, $user, '75% abgeschlossen');
        } elseif ($isCompleted) {
            $this->sendCompletionEmail($company, $user);
            $this->awardCompletionAchievements($company);
        }

        return $this->getProgress($company, $user);
    }

    /**
     * Get next step in onboarding process
     */
    protected function getNextStep(array $completedSteps): ?string
    {
        foreach (array_keys($this->steps) as $step) {
            if (!in_array($step, $completedSteps)) {
                return $step;
            }
        }
        return null;
    }

    /**
     * Validate all required steps are completed
     */
    protected function validateAllRequiredSteps(Company $company): bool
    {
        foreach ($this->steps as $key => $step) {
            if ($step['required'] && !$this->validateStep($company, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a specific step
     */
    public function validateStep(Company $company, string $step): bool
    {
        if (!isset($this->steps[$step])) {
            return false;
        }

        $validations = $this->steps[$step]['validation'];

        foreach ($validations as $validation) {
            switch ($validation) {
                case 'company_name':
                    if (empty($company->name)) return false;
                    break;
                case 'contact_person':
                    if (empty($company->contact_person)) return false;
                    break;
                case 'email':
                    if (empty($company->email)) return false;
                    break;
                case 'phone':
                    if (empty($company->phone)) return false;
                    break;
                case 'has_branches':
                    if ($company->branches()->count() === 0) return false;
                    break;
                case 'has_staff':
                    if ($company->staff()->count() === 0) return false;
                    break;
                case 'has_services':
                    if ($company->services()->count() === 0) return false;
                    break;
                case 'has_working_hours':
                    $hasWorkingHours = $company->branches()
                        ->whereHas('workingHours')
                        ->exists();
                    if (!$hasWorkingHours) return false;
                    break;
                case 'calcom_connected':
                    if (empty($company->calcom_api_key)) return false;
                    break;
                case 'retell_connected':
                    if (empty($company->retell_api_key)) return false;
                    break;
                case 'test_call_completed':
                    $testCall = $company->calls()
                        ->where('is_test_call', true)
                        ->where('created_at', '>=', now()->subDays(7))
                        ->exists();
                    if (!$testCall) return false;
                    break;
            }
        }

        return true;
    }

    /**
     * Get sample data for a step
     */
    public function getSampleData(string $step): array
    {
        $samples = [
            'branch_setup' => [
                'name' => 'Hauptfiliale',
                'address' => 'Musterstraße 123',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'phone' => '+49 30 12345678',
            ],
            'staff_setup' => [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max.mustermann@beispiel.de',
                'phone' => '+49 170 1234567',
                'role' => 'Mitarbeiter',
            ],
            'service_setup' => [
                'name' => 'Beratungsgespräch',
                'description' => '30-minütiges Beratungsgespräch',
                'duration' => 30,
                'price' => 50.00,
                'currency' => 'EUR',
            ],
            'working_hours' => [
                'monday' => ['start' => '09:00', 'end' => '18:00'],
                'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                'thursday' => ['start' => '09:00', 'end' => '18:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
                'saturday' => ['closed' => true],
                'sunday' => ['closed' => true],
            ],
        ];

        return $samples[$step] ?? [];
    }

    /**
     * Get checklist for a company
     */
    public function getChecklist(Company $company, string $type = 'getting_started'): array
    {
        $checklist = DB::table('onboarding_checklists')
            ->where('company_id', $company->id)
            ->where('checklist_type', $type)
            ->first();

        if (!$checklist) {
            $items = $this->getChecklistItems($type);
            $checklistId = DB::table('onboarding_checklists')->insertGetId([
                'company_id' => $company->id,
                'checklist_type' => $type,
                'items' => json_encode($items),
                'completed_items' => json_encode([]),
                'progress_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $checklist = DB::table('onboarding_checklists')->find($checklistId);
        }

        return [
            'id' => $checklist->id,
            'type' => $checklist->checklist_type,
            'items' => json_decode($checklist->items, true),
            'completed_items' => json_decode($checklist->completed_items, true) ?: [],
            'progress_percentage' => $checklist->progress_percentage,
        ];
    }

    /**
     * Update checklist item status
     */
    public function updateChecklistItem(Company $company, string $type, string $itemKey, bool $completed): void
    {
        $checklist = $this->getChecklist($company, $type);
        $completedItems = $checklist['completed_items'];

        if ($completed && !in_array($itemKey, $completedItems)) {
            $completedItems[] = $itemKey;
        } elseif (!$completed && in_array($itemKey, $completedItems)) {
            $completedItems = array_diff($completedItems, [$itemKey]);
        }

        $progressPercentage = count($completedItems) / count($checklist['items']) * 100;

        DB::table('onboarding_checklists')
            ->where('id', $checklist['id'])
            ->update([
                'completed_items' => json_encode(array_values($completedItems)),
                'progress_percentage' => round($progressPercentage),
                'updated_at' => now(),
            ]);
    }

    /**
     * Get checklist items based on type
     */
    protected function getChecklistItems(string $type): array
    {
        $items = [
            'getting_started' => [
                'complete_company_profile' => 'Unternehmensprofil vervollständigen',
                'add_first_branch' => 'Ersten Standort hinzufügen',
                'add_first_staff' => 'Ersten Mitarbeiter hinzufügen',
                'create_first_service' => 'Erste Dienstleistung erstellen',
                'set_working_hours' => 'Arbeitszeiten festlegen',
                'connect_calendar' => 'Kalender verbinden',
                'make_test_call' => 'Testanruf durchführen',
            ],
            'advanced' => [
                'customize_ai_agent' => 'KI-Agent personalisieren',
                'setup_email_templates' => 'E-Mail-Vorlagen anpassen',
                'configure_notifications' => 'Benachrichtigungen konfigurieren',
                'add_team_members' => 'Teammitglieder einladen',
                'setup_reporting' => 'Berichte einrichten',
                'configure_webhooks' => 'Webhooks konfigurieren',
            ],
            'integration' => [
                'connect_calcom' => 'Cal.com verbinden',
                'setup_retell' => 'Retell.ai einrichten',
                'configure_stripe' => 'Stripe-Zahlungen aktivieren',
                'setup_crm' => 'CRM-Integration einrichten',
                'enable_analytics' => 'Analytics aktivieren',
            ],
        ];

        return $items[$type] ?? $items['getting_started'];
    }

    /**
     * Send milestone email
     */
    protected function sendMilestoneEmail(Company $company, User $user, string $milestone): void
    {
        try {
            Mail::to($user->email)->send(new OnboardingStepEmail($company, $user, $milestone));
        } catch (\Exception $e) {
            Log::error('Failed to send onboarding milestone email', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'milestone' => $milestone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send completion email
     */
    protected function sendCompletionEmail(Company $company, User $user): void
    {
        try {
            Mail::to($user->email)->send(new OnboardingStepEmail($company, $user, 'completed'));
        } catch (\Exception $e) {
            Log::error('Failed to send onboarding completion email', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Award completion achievements
     */
    protected function awardCompletionAchievements(Company $company): void
    {
        $achievements = [
            'onboarding_complete' => 'Onboarding abgeschlossen',
            'fast_learner' => 'Schnelllerner (unter 30 Minuten)',
            'fully_configured' => 'Vollständig konfiguriert',
        ];

        foreach ($achievements as $key => $name) {
            $this->awardAchievement($company, $key);
        }
    }

    /**
     * Award achievement to company
     */
    public function awardAchievement(Company $company, string $achievementKey): void
    {
        $achievement = DB::table('onboarding_achievements')
            ->where('key', $achievementKey)
            ->where('is_active', true)
            ->first();

        if (!$achievement) {
            return;
        }

        $exists = DB::table('company_achievements')
            ->where('company_id', $company->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if (!$exists) {
            DB::table('company_achievements')->insert([
                'company_id' => $company->id,
                'achievement_id' => $achievement->id,
                'earned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get company achievements
     */
    public function getAchievements(Company $company): Collection
    {
        return DB::table('company_achievements')
            ->join('onboarding_achievements', 'company_achievements.achievement_id', '=', 'onboarding_achievements.id')
            ->where('company_achievements.company_id', $company->id)
            ->select('onboarding_achievements.*', 'company_achievements.earned_at')
            ->get();
    }

    /**
     * Get available tutorials for a page
     */
    public function getTutorialsForPage(string $pageRoute): Collection
    {
        return DB::table('onboarding_tutorials')
            ->where('page_route', $pageRoute)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Mark tutorial as viewed/completed
     */
    public function markTutorialProgress(User $user, int $tutorialId, bool $completed = false): void
    {
        $exists = DB::table('user_tutorial_progress')
            ->where('user_id', $user->id)
            ->where('tutorial_id', $tutorialId)
            ->first();

        if ($exists) {
            DB::table('user_tutorial_progress')
                ->where('user_id', $user->id)
                ->where('tutorial_id', $tutorialId)
                ->update([
                    'is_viewed' => true,
                    'is_completed' => $completed,
                    'viewed_at' => $exists->viewed_at ?? now(),
                    'completed_at' => $completed ? now() : null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('user_tutorial_progress')->insert([
                'user_id' => $user->id,
                'tutorial_id' => $tutorialId,
                'is_viewed' => true,
                'is_completed' => $completed,
                'viewed_at' => now(),
                'completed_at' => $completed ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get all defined steps
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Calculate overall readiness score
     */
    public function getReadinessScore(Company $company): int
    {
        $scores = [
            'profile_complete' => $this->isProfileComplete($company) ? 20 : 0,
            'has_branches' => $company->branches()->count() > 0 ? 20 : 0,
            'has_staff' => $company->staff()->count() > 0 ? 20 : 0,
            'has_services' => $company->services()->count() > 0 ? 20 : 0,
            'integrations_ready' => $this->areIntegrationsReady($company) ? 20 : 0,
        ];

        return array_sum($scores);
    }

    /**
     * Check if company profile is complete
     */
    protected function isProfileComplete(Company $company): bool
    {
        $requiredFields = ['name', 'email', 'phone', 'address', 'city', 'postal_code'];
        
        foreach ($requiredFields as $field) {
            if (empty($company->$field)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if integrations are ready
     */
    protected function areIntegrationsReady(Company $company): bool
    {
        return !empty($company->calcom_api_key) || !empty($company->retell_api_key);
    }
}