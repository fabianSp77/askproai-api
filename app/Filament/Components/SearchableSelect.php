<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class SearchableSelect
{
    /**
     * Create enhanced searchable select for forms
     */
    public static function make(string $name): Select
    {
        return Select::make($name)
            ->searchable()
            ->preload()
            ->native(false)
            ->optionsLimit(50)
            ->searchDebounce(300)
            ->loadingMessage('Lade Optionen...')
            ->noSearchResultsMessage('Keine Ergebnisse gefunden')
            ->searchPrompt('Suchen...')
            ->searchingMessage('Suche...');
    }

    /**
     * Create customer select
     */
    public static function customer(string $name = 'customer_id'): Select
    {
        return static::make($name)
            ->label('Kunde')
            ->relationship(
                name: 'customer',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query) => $query
            )
            ->getSearchResultsUsing(fn (string $search) => 
                \App\Models\Customer::where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
            )
            ->getOptionLabelUsing(fn ($value): ?string => 
                \App\Models\Customer::find($value)?->name
            )
            ->createOptionForm([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('email')
                    ->label('E-Mail')
                    ->email()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->maxLength(255),
            ])
            ->createOptionUsing(function (array $data) {
                $customer = \App\Models\Customer::create($data);
                return $customer->id;
            });
    }

    /**
     * Create staff select
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
            ->getOptionLabelUsing(function ($value): ?string {
                $staff = \App\Models\Staff::find($value);
                if (!$staff) return null;
                
                return $staff->name . ($staff->homeBranch ? ' - ' . $staff->homeBranch->name : '');
            });
    }

    /**
     * Create service select
     */
    public static function service(string $name = 'service_id'): Select
    {
        return static::make($name)
            ->label('Service')
            ->relationship(
                name: 'service',
                titleAttribute: 'name'
            )
            ->getOptionLabelUsing(function ($value): ?string {
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
     * Create branch select
     */
    public static function branch(string $name = 'branch_id'): Select
    {
        return static::make($name)
            ->label('Filiale')
            ->relationship(
                name: 'branch',
                titleAttribute: 'name'
            )
            ->getOptionLabelUsing(function ($value): ?string {
                $branch = \App\Models\Branch::find($value);
                if (!$branch) return null;
                
                $label = $branch->name;
                if ($branch->city) {
                    $label .= ' - ' . $branch->city;
                }
                
                return $label;
            });
    }

    /**
     * Create company select
     */
    public static function company(string $name = 'company_id'): Select
    {
        return static::make($name)
            ->label('Unternehmen')
            ->relationship(
                name: 'company',
                titleAttribute: 'name'
            )
            ->visible(fn () => auth()->user() && auth()->user()->hasRole(['super_admin', 'admin']));
    }

    /**
     * Create status select with colors
     */
    public static function status(string $name = 'status', array $options = []): Select
    {
        $defaultOptions = [
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestätigt',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgesagt',
        ];

        return static::make($name)
            ->label('Status')
            ->options($options ?: $defaultOptions)
            ->native(false);
    }

    /**
     * Create time slot select
     */
    public static function timeSlot(string $name = 'time_slot'): Select
    {
        $slots = [];
        $start = \Carbon\Carbon::createFromTime(8, 0);
        $end = \Carbon\Carbon::createFromTime(20, 0);
        
        while ($start <= $end) {
            $slots[$start->format('H:i')] = $start->format('H:i') . ' Uhr';
            $start->addMinutes(15);
        }

        return static::make($name)
            ->label('Zeitslot')
            ->options($slots)
            ->native(false);
    }

    /**
     * Create priority select
     */
    public static function priority(string $name = 'priority'): Select
    {
        return static::make($name)
            ->label('Priorität')
            ->options([
                'low' => 'Niedrig',
                'medium' => 'Mittel',
                'high' => 'Hoch',
                'urgent' => 'Dringend',
            ])
            ->default('medium');
    }

    /**
     * Create filter for tables
     */
    public static function filter(string $name, string $relationship, string $titleAttribute = 'name'): SelectFilter
    {
        return SelectFilter::make($name)
            ->relationship($relationship, $titleAttribute)
            ->searchable()
            ->preload()
            ->multiple()
            ->indicator(ucfirst(str_replace('_', ' ', $relationship)));
    }
}