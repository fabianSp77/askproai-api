<?php

namespace App\Filament\Resources\CallbackRequestResource\Pages;

use App\Filament\Resources\CallbackRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CallbackRequest;

class ListCallbackRequests extends ListRecords
{
    protected static string $resource = CallbackRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->badge(CallbackRequest::count()),

            'pending' => Tab::make('Ausstehend')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallbackRequest::STATUS_PENDING))
                ->badge(CallbackRequest::where('status', CallbackRequest::STATUS_PENDING)->count())
                ->badgeColor('warning'),

            'assigned' => Tab::make('Zugewiesen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallbackRequest::STATUS_ASSIGNED))
                ->badge(CallbackRequest::where('status', CallbackRequest::STATUS_ASSIGNED)->count())
                ->badgeColor('info'),

            'contacted' => Tab::make('Kontaktiert')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallbackRequest::STATUS_CONTACTED))
                ->badge(CallbackRequest::where('status', CallbackRequest::STATUS_CONTACTED)->count())
                ->badgeColor('primary'),

            'overdue' => Tab::make('Überfällig')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(CallbackRequest::overdue()->count())
                ->badgeColor('danger'),

            'completed' => Tab::make('Abgeschlossen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallbackRequest::STATUS_COMPLETED))
                ->badge(CallbackRequest::where('status', CallbackRequest::STATUS_COMPLETED)->count())
                ->badgeColor('success'),

            'urgent' => Tab::make('Dringend')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('priority', CallbackRequest::PRIORITY_URGENT)
                        ->whereNotIn('status', [CallbackRequest::STATUS_COMPLETED, CallbackRequest::STATUS_CANCELLED])
                )
                ->badge(CallbackRequest::where('priority', CallbackRequest::PRIORITY_URGENT)
                    ->whereNotIn('status', [CallbackRequest::STATUS_COMPLETED, CallbackRequest::STATUS_CANCELLED])
                    ->count())
                ->badgeColor('danger'),
        ];
    }
}
