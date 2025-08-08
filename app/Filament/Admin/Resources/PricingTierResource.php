<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PricingTierResource\Pages;
use App\Models\SecureCompanyPricingTier as CompanyPricingTier;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;

use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class PricingTierResource extends BaseResource
{
    protected static ?string $model = CompanyPricingTier::class; // Using SecureCompanyPricingTier via alias

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static ?string $navigationGroupKey = 'partners';
    
    protected static ?string $navigationLabel = 'Preismodelle';
    
    protected static ?string $modelLabel = 'Preismodell';
    
    protected static ?string $pluralModelLabel = 'Preismodelle';
    
    protected static ?int $navigationSort = 620;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        // Allow super admin or check for specific roles
        // Check role names case-insensitively
        $userRoles = $user->roles->pluck('name')->map(function ($role) {
            return strtolower(str_replace(' ', '_', $role));
        })->toArray();
        
        if (in_array('super_admin', $userRoles)) {
            return true;
        }
        
        // Check for reseller roles
        if ($user->hasRole(['reseller_owner', 'reseller_admin'])) {
            return true;
        }
        
        // Fallback: allow if user has any admin-level role
        if ($user->hasRole(['admin', 'owner'])) {
            return true;
        }
        
        // Check permissions safely
        try {
            return $user->can('viewAny', static::getModel());
        } catch (\Exception $e) {
            // If permission/policy doesn't exist, deny access
            return false;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Kunde & Typ')
                    ->schema([
                        Select::make('child_company_id')
                            ->label('Kunde')
                            ->options(function () {
                                $user = auth()->user();
                                if (!$user) {
                                    return [];
                                }
                                
                                // Check for super admin or admin roles (case insensitive)
                                $userRoles = $user->roles->pluck('name')->map(function ($role) {
                                    return strtolower(str_replace(' ', '_', $role));
                                })->toArray();
                                
                                if (in_array('super_admin', $userRoles) || in_array('admin', $userRoles)) {
                                    return Company::where('company_type', 'client')
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }
                                
                                // For resellers, show their child companies
                                if ($user->company && $user->company->isReseller()) {
                                    return $user->company->childCompanies()
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }
                                
                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn ($record) => $record !== null),
                            
                        Select::make('pricing_type')
                            ->label('Preistyp')
                            ->options([
                                'inbound' => 'Eingehende Anrufe',
                                'outbound' => 'Ausgehende Anrufe',
                                'sms' => 'SMS',
                                'monthly' => 'Monatliche Gebühr',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                    ])
                    ->columns(2),

                Section::make('Preise')
                    ->schema([
                        TextInput::make('cost_price')
                            ->label('Einkaufspreis (€/Min)')
                            ->numeric()
                            ->step(0.0001)
                            ->prefix('€')
                            ->required()
                            ->minValue(0)
                            ->maxValue(999.9999)
                            ->visible(fn () => auth()->user() && auth()->user()->hasRole(['super_admin', 'admin', 'reseller_owner', 'reseller_admin'])),
                            
                        TextInput::make('sell_price')
                            ->label('Verkaufspreis (€/Min)')
                            ->numeric()
                            ->step(0.0001)
                            ->prefix('€')
                            ->required()
                            ->minValue(0)
                            ->maxValue(999.9999),
                            
                        TextInput::make('included_minutes')
                            ->label('Inklusive Minuten')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(999999999)
                            ->helperText('Anzahl der monatlich inkludierten Minuten'),
                            
                        TextInput::make('overage_rate')
                            ->label('Preis für Zusatzminuten')
                            ->numeric()
                            ->step(0.0001)
                            ->prefix('€')
                            ->minValue(0)
                            ->maxValue(999.9999)
                            ->helperText('Preis pro Minute nach Verbrauch der inkludierten Minuten'),
                    ])
                    ->columns(2),

                Section::make('Gebühren')
                    ->schema([
                        TextInput::make('setup_fee')
                            ->label('Einrichtungsgebühr')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(99999.99),
                            
                        TextInput::make('monthly_fee')
                            ->label('Monatliche Grundgebühr')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(99999.99),
                            
                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('childCompany.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('pricing_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'inbound' => 'Eingehend',
                        'outbound' => 'Ausgehend',
                        'sms' => 'SMS',
                        'monthly' => 'Monatlich',
                        default => ucfirst($state)
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'inbound' => 'success',
                        'outbound' => 'warning',
                        'sms' => 'info',
                        'monthly' => 'gray',
                        default => 'gray'
                    }),
                    
                TextColumn::make('cost_price')
                    ->label('EK (€/Min)')
                    ->money('EUR')
                    ->visible(fn () => auth()->user() && (auth()->user()->hasPermissionTo('reseller.pricing.view_costs') || auth()->user()->hasRole(['super_admin', 'admin']))),
                    
                TextColumn::make('sell_price')
                    ->label('VK (€/Min)')
                    ->money('EUR'),
                    
                TextColumn::make('margin')
                    ->label('Marge')
                    ->state(function (CompanyPricingTier $record): string {
                        $margin = $record->calculateMargin();
                        return number_format($margin['percentage'], 1) . '%';
                    })
                    ->color(fn ($state) => floatval($state) >= 30 ? 'success' : 'warning')
                    ->visible(fn () => auth()->user() && (auth()->user()->hasPermissionTo('reseller.pricing.view_margins') || auth()->user()->hasRole(['super_admin', 'admin']))),
                    
                TextColumn::make('included_minutes')
                    ->label('Inkl. Minuten')
                    ->numeric(),
                    
                TextColumn::make('monthly_fee')
                    ->label('Grundgebühr')
                    ->money('EUR')
                    ->placeholder('—'),
                    
                ToggleColumn::make('is_active')
                    ->label('Aktiv'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pricing_type')
                    ->label('Preistyp')
                    ->options([
                        'inbound' => 'Eingehende Anrufe',
                        'outbound' => 'Ausgehende Anrufe',
                        'sms' => 'SMS',
                        'monthly' => 'Monatliche Gebühr',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn () => auth()->user() && (auth()->user()->hasRole(['reseller_owner', 'super_admin', 'admin', 'owner']))),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user() && (auth()->user()->hasRole(['reseller_owner', 'super_admin', 'admin', 'owner']))),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingTiers::route('/'),
            'create' => Pages\CreatePricingTier::route('/create'),
            'edit' => Pages\EditPricingTier::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if (!$user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        
        // Check for super admin or admin roles (case insensitive)
        $userRoles = $user->roles->pluck('name')->map(function ($role) {
            return strtolower(str_replace(' ', '_', $role));
        })->toArray();
        
        if (in_array('super_admin', $userRoles) || in_array('admin', $userRoles)) {
            return parent::getEloquentQuery();
        }
        
        // Resellers only see their own pricing tiers
        return parent::getEloquentQuery()
            ->where('company_id', $user->company_id);
    }
    
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        // If no overage rate specified, use sell price
        if (empty($data['overage_rate'])) {
            $data['overage_rate'] = $data['sell_price'];
        }
        
        return $data;
    }
}