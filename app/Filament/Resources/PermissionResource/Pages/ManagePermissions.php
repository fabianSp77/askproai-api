<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManagePermissions extends ManageRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Berechtigung')
                ->modalHeading('Neue Berechtigung erstellen')
                ->modalWidth('lg')
                ->createAnother(false)
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Berechtigung erstellt')
                        ->body('Die neue Berechtigung wurde erfolgreich angelegt.')
                ),

            Actions\Action::make('matrix_view')
                ->label('Matrix-Ansicht')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => PermissionResource::getUrl('matrix'))
                ->tooltip('Zur Matrix-Ansicht wechseln'),

            Actions\Action::make('import_standard')
                ->label('Standard-Berechtigungen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    PermissionResource::createStandardPermissions();
                })
                ->requiresConfirmation()
                ->modalHeading('Standard-Berechtigungen importieren')
                ->modalDescription('Dies erstellt alle Standard-CRUD-Berechtigungen für die gängigen Module. Bereits vorhandene Berechtigungen werden nicht überschrieben.')
                ->modalSubmitActionLabel('Importieren'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-m-list-bullet')
                ->badge(fn () => \Spatie\Permission\Models\Permission::count()),

            'with_roles' => Tab::make('Mit Rollen')
                ->icon('heroicon-m-user-group')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('roles'))
                ->badge(fn () => \Spatie\Permission\Models\Permission::has('roles')->count())
                ->badgeColor('success'),

            'without_roles' => Tab::make('Ohne Rollen')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('roles'))
                ->badge(fn () => \Spatie\Permission\Models\Permission::doesntHave('roles')->count())
                ->badgeColor('danger'),

            'system' => Tab::make('System')
                ->icon('heroicon-m-cog')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('module', 'system'))
                ->badge(fn () => \Spatie\Permission\Models\Permission::where('module', 'system')->count())
                ->badgeColor('warning'),

            'api' => Tab::make('API')
                ->icon('heroicon-m-signal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('guard_name', 'api'))
                ->badge(fn () => \Spatie\Permission\Models\Permission::where('guard_name', 'api')->count())
                ->badgeColor('info'),
        ];
    }

    public function getTitle(): string
    {
        return 'Berechtigungen verwalten';
    }

    public function getHeading(): string
    {
        return 'Berechtigungsverwaltung';
    }

    public function getSubheading(): ?string
    {
        return 'Verwalten Sie Systemberechtigungen und weisen Sie diese Rollen zu.';
    }
}