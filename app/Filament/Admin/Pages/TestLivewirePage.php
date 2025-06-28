<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class TestLivewirePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $title = 'Test Livewire';
    protected static ?string $navigationLabel = 'Test Livewire';
    protected static ?string $slug = 'test-livewire';
    
    protected static string $view = 'filament.admin.pages.test-livewire';
    
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']);
    }
}