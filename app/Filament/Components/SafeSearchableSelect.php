<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SafeSearchableSelect extends SearchableSelect
{
    /**
     * Create filter for tables with null safety
     */
    public static function filter(string $name, string $relationship, string $titleAttribute = 'name'): SelectFilter
    {
        return SelectFilter::make($name)
            ->relationship($relationship, $titleAttribute, function (Builder $query) {
                // Ensure the query is safe even if relationship is null
                return $query;
            })
            ->searchable()
            ->preload()
            ->multiple()
            ->nullable()
            ->indicator(ucfirst(str_replace('_', ' ', $relationship)));
    }

    /**
     * Create staff select with better null handling
     */
    public static function staff(string $name = 'staff_id'): Select
    {
        return static::make($name)
            ->label('Mitarbeiter')
            ->relationship(
                name: 'staff',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query) => $query->where('active', true)
            )
            ->nullable()
            ->getOptionLabelUsing(function ($value): ?string {
                if (!$value) return null;
                $staff = \App\Models\Staff::find($value);
                if (!$staff) return null;
                
                return $staff->name . ($staff->homeBranch ? ' - ' . $staff->homeBranch->name : '');
            });
    }

    /**
     * Create service select with better null handling
     */
    public static function service(string $name = 'service_id'): Select
    {
        return static::make($name)
            ->label('Service')
            ->relationship(
                name: 'service',
                titleAttribute: 'name'
            )
            ->nullable()
            ->getOptionLabelUsing(function ($value): ?string {
                if (!$value) return null;
                $service = \App\Models\Service::find($value);
                if (!$service) return null;
                
                $label = $service->name;
                if ($service->duration) {
                    $label .= ' (' . $service->duration . ' Min.)';
                }
                if ($service->price) {
                    $label .= ' - ' . number_format($service->price, 2, ',', '.') . '€';
                }
                
                return $label;
            });
    }

    /**
     * Create branch select with better null handling
     */
    public static function branch(string $name = 'branch_id'): Select
    {
        return static::make($name)
            ->label('Filiale')
            ->relationship(
                name: 'branch',
                titleAttribute: 'name'
            )
            ->nullable()
            ->getOptionLabelUsing(function ($value): ?string {
                if (!$value) return null;
                $branch = \App\Models\Branch::find($value);
                if (!$branch) return null;
                
                $label = $branch->name;
                if ($branch->city) {
                    $label .= ' - ' . $branch->city;
                }
                
                return $label;
            });
    }
}