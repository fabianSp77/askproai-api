<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class PermissionMatrix extends Page
{
    protected static string $resource = PermissionResource::class;

    protected static string $view = 'filament.resources.permission-resource.pages.permission-matrix';

    protected static ?string $title = 'Berechtigungsmatrix';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    public Collection $permissions;
    public Collection $roles;
    public array $matrix = [];
    public string $searchTerm = '';
    public ?string $selectedModule = null;
    public bool $showOnlyAssigned = false;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // Load roles ordered by priority
        $this->roles = Role::orderBy('priority')->get();

        // Build query for permissions
        $query = Permission::query();

        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        // Apply module filter
        if ($this->selectedModule) {
            $query->where('module', $this->selectedModule);
        }

        // Apply assigned filter
        if ($this->showOnlyAssigned) {
            $query->has('roles');
        }

        // Load permissions grouped by module
        $this->permissions = $query->orderBy('module')->orderBy('name')->get();

        // Build the matrix
        $this->buildMatrix();
    }

    protected function buildMatrix(): void
    {
        $this->matrix = [];

        foreach ($this->permissions as $permission) {
            $row = [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'module' => $permission->module ?? 'Allgemein',
                'description' => $permission->description,
                'roles' => []
            ];

            foreach ($this->roles as $role) {
                $row['roles'][$role->id] = $permission->roles->contains('id', $role->id);
            }

            $this->matrix[] = $row;
        }
    }

    public function togglePermission(int $permissionId, int $roleId): void
    {
        $permission = Permission::find($permissionId);
        $role = Role::find($roleId);

        if (!$permission || !$role) {
            return;
        }

        // Check if role is system role and user has permission
        if ($role->is_system && !auth()->user()->hasRole('super-admin')) {
            Notification::make()
                ->danger()
                ->title('Keine Berechtigung')
                ->body('Systemrollen können nur von Super-Admins bearbeitet werden.')
                ->send();
            return;
        }

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
            $message = "Berechtigung '{$permission->name}' von Rolle '{$role->name}' entfernt";
        } else {
            $role->givePermissionTo($permission);
            $message = "Berechtigung '{$permission->name}' zu Rolle '{$role->name}' hinzugefügt";
        }

        Notification::make()
            ->success()
            ->title('Berechtigung aktualisiert')
            ->body($message)
            ->duration(2000)
            ->send();

        // Reload the matrix
        $this->loadData();
    }

    public function toggleAllForRole(int $roleId): void
    {
        $role = Role::find($roleId);

        if (!$role) {
            return;
        }

        // Check if role is system role
        if ($role->is_system && !auth()->user()->hasRole('super-admin')) {
            Notification::make()
                ->danger()
                ->title('Keine Berechtigung')
                ->body('Systemrollen können nur von Super-Admins bearbeitet werden.')
                ->send();
            return;
        }

        $currentPermissions = $role->permissions->pluck('id');
        $visiblePermissions = $this->permissions->pluck('id');

        // If role has all visible permissions, remove them
        if ($visiblePermissions->diff($currentPermissions)->isEmpty()) {
            $role->revokePermissionTo($this->permissions);
            $message = "Alle sichtbaren Berechtigungen von Rolle '{$role->name}' entfernt";
        } else {
            // Otherwise, grant all visible permissions
            $role->givePermissionTo($this->permissions);
            $message = "Alle sichtbaren Berechtigungen zu Rolle '{$role->name}' hinzugefügt";
        }

        Notification::make()
            ->success()
            ->title('Massenaktualisierung')
            ->body($message)
            ->send();

        $this->loadData();
    }

    public function toggleAllForPermission(int $permissionId): void
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            return;
        }

        $currentRoles = $permission->roles->pluck('id');

        // Check for system roles
        $systemRoles = $this->roles->filter(function ($role) {
            return $role->is_system && !auth()->user()->hasRole('super-admin');
        });

        if ($systemRoles->isNotEmpty()) {
            Notification::make()
                ->warning()
                ->title('Eingeschränkte Aktion')
                ->body('Systemrollen wurden übersprungen (Super-Admin erforderlich).')
                ->send();
        }

        $editableRoles = $this->roles->filter(function ($role) {
            return !$role->is_system || auth()->user()->hasRole('super-admin');
        });

        // If permission has all editable roles, remove them
        if ($editableRoles->pluck('id')->diff($currentRoles)->isEmpty()) {
            foreach ($editableRoles as $role) {
                $role->revokePermissionTo($permission);
            }
            $message = "Berechtigung '{$permission->name}' von allen bearbeitbaren Rollen entfernt";
        } else {
            // Otherwise, grant to all editable roles
            foreach ($editableRoles as $role) {
                $role->givePermissionTo($permission);
            }
            $message = "Berechtigung '{$permission->name}' zu allen bearbeitbaren Rollen hinzugefügt";
        }

        Notification::make()
            ->success()
            ->title('Massenaktualisierung')
            ->body($message)
            ->send();

        $this->loadData();
    }

    public function getModulesProperty(): array
    {
        return Permission::whereNotNull('module')
            ->distinct()
            ->pluck('module')
            ->sort()
            ->mapWithKeys(fn ($module) => [$module => ucfirst($module)])
            ->toArray();
    }

    public function updatedSearchTerm(): void
    {
        $this->loadData();
    }

    public function updatedSelectedModule(): void
    {
        $this->loadData();
    }

    public function updatedShowOnlyAssigned(): void
    {
        $this->loadData();
    }

    public function clearFilters(): void
    {
        $this->searchTerm = '';
        $this->selectedModule = null;
        $this->showOnlyAssigned = false;
        $this->loadData();
    }

    public function exportMatrix()
    {
        // Implementation for CSV export
        $csv = "Berechtigung;Beschreibung;Modul";
        foreach ($this->roles as $role) {
            $csv .= ";{$role->name}";
        }
        $csv .= "\n";

        foreach ($this->matrix as $row) {
            $csv .= "{$row['permission_name']};{$row['description']};{$row['module']}";
            foreach ($this->roles as $role) {
                $csv .= ";" . ($row['roles'][$role->id] ? 'X' : '');
            }
            $csv .= "\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'berechtigungsmatrix_' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Matrix exportieren')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->action('exportMatrix'),
        ];
    }
}