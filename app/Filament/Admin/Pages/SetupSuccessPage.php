<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Branch;
use App\Models\Company;

class SetupSuccessPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static string $view = 'filament.admin.pages.setup-success';
    protected static bool $shouldRegisterNavigation = false;
    
    public Company $company;
    public Branch $branch;
    public string $testPhoneNumber = '';
    
    public function mount(): void
    {
        $this->company = Company::latest()->first();
        $this->branch = $this->company->branches()->first();
        $this->testPhoneNumber = $this->branch->phone_number ?? '+493083793369';
    }
}