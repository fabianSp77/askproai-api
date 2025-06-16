<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Termin')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                            'pdf' => 'PDF (.pdf)',
                        ])
                        ->default('xlsx')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('from')
                        ->label('Von')
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    \Filament\Forms\Components\DatePicker::make('to')
                        ->label('Bis')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    // Export logic would go here
                    \Filament\Notifications\Notification::make()
                        ->title('Export gestartet')
                        ->body('Der Export wird vorbereitet und in Kürze heruntergeladen.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-o-squares-2x2')
                ->badge(static::getResource()::getModel()::count()),
                
            'today' => Tab::make('Heute')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('starts_at', today()))
                ->badge(static::getResource()::getModel()::whereDate('starts_at', today())->count())
                ->badgeColor('primary'),
                
            'tomorrow' => Tab::make('Morgen')
                ->icon('heroicon-o-calendar-days')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('starts_at', today()->addDay()))
                ->badge(static::getResource()::getModel()::whereDate('starts_at', today()->addDay())->count()),
                
            'this_week' => Tab::make('Diese Woche')
                ->icon('heroicon-o-calendar-days')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereBetween('starts_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]))
                ->badge(static::getResource()::getModel()::whereBetween('starts_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count()),
                
            'pending' => Tab::make('Ausstehend')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(static::getResource()::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
                
            'confirmed' => Tab::make('Bestätigt')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed'))
                ->badge(static::getResource()::getModel()::where('status', 'confirmed')->count())
                ->badgeColor('info'),
                
            'cancelled' => Tab::make('Abgesagt')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(static::getResource()::getModel()::where('status', 'cancelled')->count())
                ->badgeColor('danger'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentResource\Widgets\AppointmentStats::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            AppointmentResource\Widgets\AppointmentCalendar::class,
        ];
    }
}