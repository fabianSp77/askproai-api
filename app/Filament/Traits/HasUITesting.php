<?php

namespace App\Filament\Traits;

use Filament\Notifications\Notification;

trait HasUITesting
{
    public function mountHasUITesting(): void
    {
        if (config('app.debug') && request()->has('ui_test')) {
            $this->captureUITestState();
        }
    }
    
    protected function captureUITestState(): void
    {
        $state = [
            'component' => static::class,
            'route' => request()->route()->getName(),
            'user' => auth()->user()->email,
            'timestamp' => now()->toIso8601String(),
            'viewport' => [
                'user_agent' => request()->userAgent(),
                'is_mobile' => request()->header('X-Mobile-Request'),
            ],
            'data_state' => $this->captureDataState(),
        ];
        
        // Store in session for browser extension
        session()->put('ui_test_state', $state);
        
        // Show notification
        Notification::make()
            ->title('UI Test Mode Active')
            ->body('Screenshot markers have been added. Use browser tools to capture.')
            ->info()
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('capture')
                    ->label('Trigger Capture')
                    ->url('#')
                    ->extraAttributes([
                        'onclick' => 'window.AskProAITester?.captureAnnotated()',
                    ]),
            ])
            ->send();
    }
    
    protected function captureDataState(): array
    {
        return [
            'record_count' => method_exists($this, 'getTableQuery') 
                ? $this->getTableQuery()->count() 
                : null,
            'filters_active' => method_exists($this, 'getTableFilters')
                ? count($this->getTableFilters())
                : 0,
            'current_page' => request()->get('page', 1),
        ];
    }
}