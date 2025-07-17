<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class OnboardingWizardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    
    protected static ?string $navigationLabel = '🚀 5-Min Onboarding';
    
    protected static ?string $title = '5-Minuten Onboarding Setup';
    
    protected static string $view = 'filament.admin.pages.onboarding-wizard-page';
    
    protected static ?string $navigationGroup = 'Setup';
    
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        // Only show if company hasn't completed onboarding
        $company = auth()->user()?->company;
        if (!$company) return false;
        
        $onboardingState = $company->onboardingState;
        return !$onboardingState || !$onboardingState->is_completed;
    }
}