<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class SafeTextColumn extends TextColumn
{
    public function hasRelationship(Model $record): bool
    {
        // Prevent null record errors
        if (!$record || !$record->exists) {
            return false;
        }
        
        try {
            return parent::hasRelationship($record);
        } catch (\Throwable $e) {
            // Log error but don't crash
            logger()->warning('SafeTextColumn::hasRelationship error', [
                'error' => $e->getMessage(),
                'column' => $this->getName(),
            ]);
            return false;
        }
    }
    
    public function getState(): mixed
    {
        try {
            return parent::getState();
        } catch (\Throwable $e) {
            // Return default value on error
            return $this->getDefaultState();
        }
    }
}