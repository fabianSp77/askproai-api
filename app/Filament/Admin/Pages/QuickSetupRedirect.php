<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class QuickSetupRedirect extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static string $view = 'filament.admin.pages.quick-setup-redirect';
    protected static ?string $slug = 'quick-setup-wizard';
    protected static bool $shouldRegisterNavigation = false;
    
    public function mount(): void
    {
        // Redirect to QuickSetupWizardV2
        redirect()->route('filament.admin.pages.quick-setup-wizard-v2');
    }
}
