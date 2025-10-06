<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Rolle')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle Rollen')
                ->icon('heroicon-m-shield-check')
                ->badge(static::$resource::getEloquentQuery()->count()),

            'system' => Tab::make('Systemrollen')
                ->icon('heroicon-m-lock-closed')
                ->badge(static::$resource::getEloquentQuery()->where('is_system', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_system', true)),

            'custom' => Tab::make('Benutzerdefiniert')
                ->icon('heroicon-m-user')
                ->badge(static::$resource::getEloquentQuery()->where('is_system', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_system', false)),

            'active' => Tab::make('Mit Benutzern')
                ->icon('heroicon-m-users')
                ->badge(static::$resource::getEloquentQuery()->has('users')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->has('users')),

            'inactive' => Tab::make('Ohne Benutzer')
                ->icon('heroicon-m-user-minus')
                ->badge(static::$resource::getEloquentQuery()->doesntHave('users')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('users')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RoleResource\Widgets\RoleStatsWidget::class,
        ];
    }
}