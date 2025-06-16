<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class ErrorFallback extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $title = 'Fehler aufgetreten';
    protected static string $view = 'filament.admin.pages.error-fallback';
    protected static bool $shouldRegisterNavigation = false;
    
    public $error = null;
    public $referer = null;
    
    public function mount(): void
    {
        $this->error = session('error_fallback');
        $this->referer = request()->header('referer', '/admin');
        
        if ($this->error) {
            Log::error('ErrorFallback Page accessed', [
                'error' => $this->error,
                'referer' => $this->referer,
                'user_id' => auth()->id(),
                'url' => request()->fullUrl(),
            ]);
        }
    }
    
    public function goBack(): void
    {
        $this->redirect($this->referer);
    }
    
    public function goToDashboard(): void
    {
        $this->redirect('/admin');
    }
}