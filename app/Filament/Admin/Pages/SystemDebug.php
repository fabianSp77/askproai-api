<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class SystemDebug extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel = 'System Debug';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.admin.pages.system-debug';
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }
    
    public function testDropdown(): void
    {
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Dropdown test executed'
        ]);
    }
    
    public function getSystemInfo(): array
    {
        return [
            'livewire_version' => class_exists(\Livewire\Livewire::class) ? 'v3' : 'Not loaded',
            'alpine_loaded' => 'Check browser console',
            'filament_version' => '3.3.14',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'session_driver' => config('session.driver'),
            'current_portal' => session('current_portal', 'unknown'),
        ];
    }
}