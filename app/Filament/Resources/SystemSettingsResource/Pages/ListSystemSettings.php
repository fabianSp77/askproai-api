<?php

namespace App\Filament\Resources\SystemSettingsResource\Pages;

use App\Filament\Resources\SystemSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SystemSetting;

class ListSystemSettings extends ListRecords
{
    protected static string $resource = SystemSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Einstellung')
                ->icon('heroicon-m-plus'),
            
            Actions\Action::make('import')
                ->label('Importieren')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('JSON Datei')
                        ->required()
                        ->acceptedFileTypes(['application/json'])
                        ->maxSize(1024),
                ])
                ->action(function (array $data) {
                    $content = file_get_contents(storage_path('app/public/' . $data['file']));
                    $settings = json_decode($content, true);
                    
                    foreach ($settings as $setting) {
                        SystemSetting::updateOrCreate(
                            ['key' => $setting['key']],
                            $setting
                        );
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Import erfolgreich')
                        ->body(count($settings) . ' Einstellungen wurden importiert.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-m-cog-6-tooth')
                ->badge(SystemSetting::count()),

            'general' => Tab::make('Allgemein')
                ->icon('heroicon-m-adjustments-horizontal')
                ->badge(SystemSetting::where('group', SystemSetting::GROUP_GENERAL)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', SystemSetting::GROUP_GENERAL)),

            'security' => Tab::make('Sicherheit')
                ->icon('heroicon-m-lock-closed')
                ->badge(SystemSetting::where('group', SystemSetting::GROUP_SECURITY)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', SystemSetting::GROUP_SECURITY)),

            'email' => Tab::make('E-Mail')
                ->icon('heroicon-m-envelope')
                ->badge(SystemSetting::where('group', SystemSetting::GROUP_EMAIL)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', SystemSetting::GROUP_EMAIL)),

            'performance' => Tab::make('Performance')
                ->icon('heroicon-m-bolt')
                ->badge(SystemSetting::where('group', SystemSetting::GROUP_PERFORMANCE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', SystemSetting::GROUP_PERFORMANCE)),

            'maintenance' => Tab::make('Wartung')
                ->icon('heroicon-m-wrench-screwdriver')
                ->badge(SystemSetting::where('group', SystemSetting::GROUP_MAINTENANCE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', SystemSetting::GROUP_MAINTENANCE)),
        ];
    }
}