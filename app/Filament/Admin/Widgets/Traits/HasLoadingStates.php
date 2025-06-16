<?php

namespace App\Filament\Admin\Widgets\Traits;

trait HasLoadingStates
{
    public bool $isLoading = false;
    public ?string $loadingMessage = null;
    public ?string $errorMessage = null;
    
    public function startLoading(?string $message = null): void
    {
        $this->isLoading = true;
        $this->loadingMessage = $message ?? 'Daten werden geladen...';
        $this->errorMessage = null;
    }
    
    public function stopLoading(): void
    {
        $this->isLoading = false;
        $this->loadingMessage = null;
    }
    
    public function setError(string $message): void
    {
        $this->isLoading = false;
        $this->errorMessage = $message;
    }
    
    public function clearError(): void
    {
        $this->errorMessage = null;
    }
    
    protected function withErrorHandling(callable $callback)
    {
        try {
            $this->startLoading();
            $result = $callback();
            $this->stopLoading();
            return $result;
        } catch (\Exception $e) {
            $this->setError('Fehler beim Laden der Daten: ' . $e->getMessage());
            report($e);
            return null;
        }
    }
}