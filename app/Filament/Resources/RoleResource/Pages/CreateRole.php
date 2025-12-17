<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Create default permissions if first custom role
        if (Role::where('is_system', false)->count() === 1) {
            $this->createDefaultPermissions();
        }
    }

    private function createDefaultPermissions(): void
    {
        $modules = [
            'company' => 'Firmen',
            'branch' => 'Niederlassungen',
            'staff' => 'Mitarbeiter',
            'service' => 'Dienstleistungen',
            'user' => 'Benutzer',
            'role' => 'Rollen',
            'setting' => 'Einstellungen',
            'integration' => 'Integrationen',
        ];

        $actions = [
            'view' => 'Anzeigen',
            'create' => 'Erstellen',
            'update' => 'Bearbeiten',
            'delete' => 'Löschen',
            'export' => 'Exportieren',
            'import' => 'Importieren',
        ];

        foreach ($modules as $module => $moduleLabel) {
            foreach ($actions as $action => $actionLabel) {
                Permission::firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    [
                        'guard_name' => 'web',
                    ]
                );
            }
        }
    }
}