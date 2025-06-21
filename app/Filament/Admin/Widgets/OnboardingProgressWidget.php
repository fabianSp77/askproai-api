<?php

namespace App\Filament\Admin\Widgets;

use App\Services\OnboardingService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OnboardingProgressWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.onboarding-progress-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -3;
    
    public $progress;
    public $checklist;
    public $achievements;
    public $readinessScore;
    public $nextSteps;

    public function mount(): void
    {
        $company = Auth::user()->company;
        if (!$company) {
            return;
        }

        $onboardingService = app(OnboardingService::class);
        
        $this->progress = $onboardingService->getProgress($company, Auth::user());
        $this->checklist = $onboardingService->getChecklist($company, 'getting_started');
        $this->achievements = $onboardingService->getAchievements($company);
        $this->readinessScore = $onboardingService->getReadinessScore($company);
        
        // Get next recommended steps
        $this->nextSteps = $this->getNextRecommendedSteps();
    }

    protected function getNextRecommendedSteps(): array
    {
        $steps = [];
        $company = Auth::user()->company;
        
        if (!$company) {
            return $steps;
        }

        // Check what's missing and recommend next steps
        if ($company->branches()->count() === 0) {
            $steps[] = [
                'title' => 'Standort hinzufügen',
                'description' => 'Fügen Sie mindestens einen Geschäftsstandort hinzu',
                'action' => 'filament.admin.resources.branches.create',
                'icon' => 'heroicon-o-map-pin',
            ];
        }
        
        if ($company->staff()->count() === 0) {
            $steps[] = [
                'title' => 'Mitarbeiter anlegen',
                'description' => 'Fügen Sie Ihre Mitarbeiter zum System hinzu',
                'action' => 'filament.admin.resources.staff.create',
                'icon' => 'heroicon-o-users',
            ];
        }
        
        if ($company->services()->count() === 0) {
            $steps[] = [
                'title' => 'Dienstleistungen erstellen',
                'description' => 'Definieren Sie die buchbaren Dienstleistungen',
                'action' => 'filament.admin.resources.services.create',
                'icon' => 'heroicon-o-briefcase',
            ];
        }
        
        if (empty($company->calcom_api_key) && empty($company->retell_api_key)) {
            $steps[] = [
                'title' => 'Integrationen einrichten',
                'description' => 'Verbinden Sie Cal.com oder Retell.ai',
                'action' => 'filament.admin.pages.onboarding',
                'icon' => 'heroicon-o-link',
            ];
        }
        
        return array_slice($steps, 0, 3); // Return max 3 recommendations
    }

    public static function canView(): bool
    {
        // Temporarily disable widget to fix 500 error
        return false;
        
        $company = Auth::user()->company ?? null;
        if (!$company) {
            return false;
        }
        
        // Show widget if onboarding is not complete or readiness score is below 80%
        $onboardingService = app(OnboardingService::class);
        $progress = $onboardingService->getProgress($company, Auth::user());
        $readinessScore = $onboardingService->getReadinessScore($company);
        
        return !$progress['is_completed'] || $readinessScore < 80;
    }
}