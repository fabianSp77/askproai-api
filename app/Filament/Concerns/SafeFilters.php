<?php

namespace App\Filament\Concerns;

use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;

trait SafeFilters
{
    protected function applySafeFilters($table)
    {
        try {
            return $table->filters($this->getTableFilters());
        } catch (\Exception $e) {
            Log::error('Filter application failed', [
                'error' => $e->getMessage(),
                'resource' => static::class,
            ]);
            
            // Return table without filters to prevent redirect
            return $table;
        }
    }
    
    protected function handleFilterError(\Exception $e): void
    {
        Log::error('Filter error in ' . static::class, [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Show notification instead of redirecting
        \Filament\Notifications\Notification::make()
            ->title('Filter-Fehler')
            ->body('Es gab ein Problem mit den Filtern. Bitte laden Sie die Seite neu.')
            ->danger()
            ->send();
    }
}