<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clean_old')
                ->label('🗑️ Alte Logs löschen')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Alte Logs löschen')
                ->modalDescription('Logs älter als 90 Tage werden gelöscht.')
                ->modalSubmitActionLabel('Löschen')
                ->action(function () {
                    $deleted = ActivityLog::cleanOldLogs(90);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Logs gelöscht')
                        ->body("{$deleted} alte Logs wurden entfernt.")
                        ->success()
                        ->send();
                })
                ->visible(fn () => auth()->user()->hasRole('super-admin')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-m-list-bullet')
                ->badge(ActivityLog::count()),

            'today' => Tab::make('Heute')
                ->icon('heroicon-m-calendar')
                ->badge(ActivityLog::today()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->today()),

            'auth' => Tab::make('Authentifizierung')
                ->icon('heroicon-m-lock-closed')
                ->badge(ActivityLog::ofType(ActivityLog::TYPE_AUTH)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofType(ActivityLog::TYPE_AUTH)),

            'errors' => Tab::make('Fehler')
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(ActivityLog::ofType(ActivityLog::TYPE_ERROR)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->ofType(ActivityLog::TYPE_ERROR)),

            'high_severity' => Tab::make('Kritisch')
                ->icon('heroicon-m-shield-exclamation')
                ->badge(ActivityLog::highSeverity()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->highSeverity()),

            'api' => Tab::make('API')
                ->icon('heroicon-m-server')
                ->badge(ActivityLog::ofType(ActivityLog::TYPE_API)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofType(ActivityLog::TYPE_API)),

            'data' => Tab::make('Datenänderungen')
                ->icon('heroicon-m-circle-stack')
                ->badge(ActivityLog::ofType(ActivityLog::TYPE_DATA)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofType(ActivityLog::TYPE_DATA)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityLogResource\Widgets\ActivityStatsWidget::class,
        ];
    }
}