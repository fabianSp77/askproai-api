<?php

namespace App\Filament\Admin\Traits;

use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

trait HasLoadingStates
{
    /**
     * Loading states for form fields
     */
    public array $loadingStates = [];
    
    /**
     * Error states for form fields
     */
    public array $errorStates = [];
    
    /**
     * Set loading state for a field
     */
    protected function setLoading(string $field, bool $loading = true): void
    {
        $this->loadingStates[$field] = $loading;
    }
    
    /**
     * Check if field is loading
     */
    protected function isLoading(string $field): bool
    {
        return $this->loadingStates[$field] ?? false;
    }
    
    /**
     * Set error state for a field
     */
    protected function setError(string $field, ?string $error = null): void
    {
        if ($error) {
            $this->errorStates[$field] = $error;
        } else {
            unset($this->errorStates[$field]);
        }
    }
    
    /**
     * Get error for a field
     */
    protected function getError(string $field): ?string
    {
        return $this->errorStates[$field] ?? null;
    }
    
    /**
     * Create a select field with loading state
     */
    protected function makeLoadingSelect(string $name): Select
    {
        return Select::make($name)
            ->loadingMessage(fn() => $this->isLoading($name) ? 'Lädt...' : null)
            ->helperText(fn() => $this->getError($name))
            ->extraAttributes(fn() => $this->isLoading($name) ? ['class' => 'opacity-50'] : []);
    }
    
    /**
     * Load options with error handling
     */
    protected function loadOptionsWithErrorHandling(
        string $field,
        callable $loader,
        string $errorMessage = 'Fehler beim Laden der Optionen'
    ): array {
        $this->setLoading($field, true);
        $this->setError($field, null);
        
        try {
            $options = $loader();
            $this->setLoading($field, false);
            return $options;
        } catch (\Exception $e) {
            $this->setLoading($field, false);
            $this->setError($field, $errorMessage);
            
            Notification::make()
                ->title($errorMessage)
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            return [];
        }
    }
    
    /**
     * Reset all loading and error states
     */
    protected function resetStates(): void
    {
        $this->loadingStates = [];
        $this->errorStates = [];
    }
}