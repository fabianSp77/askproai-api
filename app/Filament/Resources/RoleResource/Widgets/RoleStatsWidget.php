<?php

namespace App\Filament\Resources\RoleResource\Widgets;

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RoleStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalRoles = Role::count();
        $systemRoles = Role::where('is_system', true)->count();
        $customRoles = Role::where('is_system', false)->count();
        $totalPermissions = Permission::count();
        $usersWithRoles = User::whereHas('roles')->count();
        $activeRoles = Role::has('users')->count();

        return [
            Stat::make('Gesamtrollen', $totalRoles)
                ->description("$systemRoles System / $customRoles Benutzerdefiniert")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('primary')
                ->chart([7, 8, 9, 8, 10, 11, 12]),

            Stat::make('Aktive Rollen', $activeRoles)
                ->description($activeRoles > 0
                    ? round(($activeRoles / $totalRoles) * 100, 1) . '% mit Benutzern'
                    : 'Keine aktiven Rollen')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Berechtigungen', $totalPermissions)
                ->description('Verfügbare Systemberechtigungen')
                ->descriptionIcon('heroicon-m-key')
                ->color('info'),

            Stat::make('Benutzer mit Rollen', $usersWithRoles)
                ->description(User::count() > 0
                    ? round(($usersWithRoles / User::count()) * 100, 1) . '% aller Benutzer'
                    : 'Keine Benutzer')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}