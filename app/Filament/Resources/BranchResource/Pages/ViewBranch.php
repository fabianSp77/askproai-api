<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Filament\Notifications\Notification;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    /**
     * Handle record resolution with graceful error handling.
     *
     * FIX: Prevents 500 errors when Branch record doesn't exist or is outside user's company scope.
     * Shows user-friendly notification and redirects to list page.
     */
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        try {
            return parent::resolveRecord($key);
        } catch (ModelNotFoundException $e) {
            // Record not found (either doesn't exist or filtered by CompanyScope)
            Notification::make()
                ->title('Filiale nicht gefunden')
                ->body('Diese Filiale existiert nicht oder gehört zu einem anderen Unternehmen.')
                ->danger()
                ->send();

            // Redirect to list page
            $this->redirect(BranchResource::getUrl('index'));

            // Return dummy model to prevent further errors
            // (redirect will happen before rendering)
            return new \App\Models\Branch();
        }
    }
}