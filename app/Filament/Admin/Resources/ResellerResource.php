<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ResellerResource\Pages;
use App\Filament\Admin\Resources\ResellerResource\RelationManagers;
use App\Filament\Admin\Resources\ResellerResource\Widgets;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResellerResource extends BaseResource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroupKey = 'partners';
    
    protected static ?int $navigationSort = 610;

    public static function getNavigationLabel(): string
    {
        return 'Resellers';
    }

    public static function getPluralLabel(): string
    {
        return 'Resellers';
    }

    public static function getLabel(): string
    {
        return 'Reseller';
    }

    // Only show reseller companies in this resource
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_type', 'reseller');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // Basic Information
                    Forms\Components\Wizard\Step::make('Basic Information')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Forms\Components\Section::make('Reseller Details')
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Reseller Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-building-office-2'),

                                            Forms\Components\TextInput::make('email')
                                                ->label('Contact Email')
                                                ->email()
                                                ->required()
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-envelope'),
                                        ])
                                        ->columns([
                                            'default' => 1,
                                            'sm' => 2,
                                        ]),

                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('phone')
                                                ->label('Phone Number')
                                                ->tel()
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-phone'),

                                            Forms\Components\TextInput::make('website')
                                                ->label('Website')
                                                ->url()
                                                ->columnSpan(1)
                                                ->prefixIcon('heroicon-o-globe-alt'),
                                        ])
                                        ->columns([
                                            'default' => 1,
                                            'sm' => 2,
                                        ]),

                                    Forms\Components\Textarea::make('address')
                                        ->label('Business Address')
                                        ->rows(3),

                                    Forms\Components\Hidden::make('company_type')
                                        ->default('reseller'),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active Reseller')
                                        ->default(true)
                                        ->helperText('Inactive resellers cannot create new clients'),
                                ]),
                        ]),

                    // Commission & Pricing
                    Forms\Components\Wizard\Step::make('Commission & Pricing')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\Section::make('Commission Structure')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('commission_rate')
                                            ->label('Commission Rate (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->columnSpan(1)
                                            ->helperText('Percentage of revenue shared with reseller'),

                                        Forms\Components\Select::make('commission_type')
                                            ->label('Commission Type')
                                            ->options([
                                                'percentage' => 'Percentage of Revenue',
                                                'fixed' => 'Fixed Amount per Client',
                                                'tiered' => 'Tiered Structure',
                                            ])
                                            ->default('percentage')
                                            ->columnSpan(1),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('credit_limit')
                                            ->label('Credit Limit')
                                            ->numeric()
                                            ->prefix('€')
                                            ->columnSpan(1)
                                            ->helperText('Maximum outstanding balance'),

                                        Forms\Components\Select::make('payment_terms')
                                            ->label('Payment Terms')
                                            ->options([
                                                'net_15' => 'Net 15 days',
                                                'net_30' => 'Net 30 days',
                                                'net_60' => 'Net 60 days',
                                                'prepaid' => 'Prepaid only',
                                            ])
                                            ->default('net_30')
                                            ->columnSpan(1),
                                    ]),
                                ]),
                        ]),

                    // White Label Settings
                    Forms\Components\Wizard\Step::make('White Label')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            Forms\Components\Section::make('Branding Options')
                                ->schema([
                                    Forms\Components\Toggle::make('is_white_label')
                                        ->label('Enable White Label')
                                        ->reactive()
                                        ->helperText('Allow reseller to use their own branding'),

                                    Forms\Components\Group::make([
                                        Forms\Components\FileUpload::make('white_label_settings.logo')
                                            ->label('Custom Logo')
                                            ->image()
                                            ->directory('reseller-logos')
                                            ->visibility('private'),

                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\ColorPicker::make('white_label_settings.primary_color')
                                                ->label('Primary Color')
                                                ->default('#3B82F6'),

                                            Forms\Components\ColorPicker::make('white_label_settings.secondary_color')
                                                ->label('Secondary Color')
                                                ->default('#6B7280'),

                                            Forms\Components\ColorPicker::make('white_label_settings.accent_color')
                                                ->label('Accent Color')
                                                ->default('#F59E0B'),
                                        ]),

                                        Forms\Components\TextInput::make('white_label_settings.company_name')
                                            ->label('Display Company Name')
                                            ->helperText('Name shown to end customers'),

                                        Forms\Components\Textarea::make('white_label_settings.custom_footer')
                                            ->label('Custom Footer Text')
                                            ->rows(2),
                                    ])
                                    ->visible(fn ($get) => $get('is_white_label')),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-company.png'))
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Company $record): string => $record->email ?? '')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('childCompanies_count')
                    ->label('Clients')
                    ->counts('childCompanies')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-users'),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('revenue_ytd')
                    ->label('YTD Revenue')
                    ->money('EUR')
                    ->sortable()
                    ->color('success')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Credit Limit')
                    ->money('EUR')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_white_label')
                    ->label('White Label')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All resellers')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_white_label')
                    ->label('White Label')
                    ->placeholder('All')
                    ->trueLabel('White label enabled')
                    ->falseLabel('Standard branding'),

                Tables\Filters\Filter::make('has_clients')
                    ->label('Has Clients')
                    ->query(fn (Builder $query): Builder => $query->has('childCompanies')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('view_clients')
                    ->label('View Clients')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->url(fn (Company $record): string => CompanyResource::getUrl('index', ['tableFilters[parent_company_id][value]' => $record->id])),

                Tables\Actions\Action::make('create_client')
                    ->label('Add Client')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn (Company $record): string => CompanyResource::getUrl('create', ['parent_company_id' => $record->id])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Reseller')
                    ->icon('heroicon-o-plus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Reseller Overview')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\ImageEntry::make('logo')
                                    ->hiddenLabel()
                                    ->circular()
                                    ->size(80),

                                Components\Group::make([
                                    Components\TextEntry::make('name')
                                        ->label('Reseller Name')
                                        ->size('lg')
                                        ->weight('bold'),
                                    
                                    Components\TextEntry::make('email')
                                        ->label('Contact Email')
                                        ->copyable()
                                        ->icon('heroicon-o-envelope'),
                                    
                                    Components\TextEntry::make('phone')
                                        ->label('Phone')
                                        ->icon('heroicon-o-phone'),
                                ])
                                ->columnSpan(2),
                            ]),
                    ]),

                Components\Section::make('Business Metrics')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('childCompanies_count')
                                    ->label('Total Clients')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('revenue_ytd')
                                    ->label('YTD Revenue')
                                    ->money('EUR')
                                    ->color('success'),

                                Components\TextEntry::make('commission_rate')
                                    ->label('Commission Rate')
                                    ->suffix('%')
                                    ->badge()
                                    ->color('warning'),

                                Components\TextEntry::make('credit_limit')
                                    ->label('Credit Limit')
                                    ->money('EUR'),
                            ]),
                    ]),

                Components\Section::make('Settings')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\IconEntry::make('is_active')
                                    ->label('Active Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Components\IconEntry::make('is_white_label')
                                    ->label('White Label Enabled')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResellers::route('/'),
            'create' => Pages\CreateReseller::route('/create'),
            'view' => Pages\ViewReseller::route('/{record}'),
            'edit' => Pages\EditReseller::route('/{record}/edit'),
            'dashboard' => Pages\ResellerDashboard::route('/{record}/dashboard'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ClientsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ResellerStatsOverview::class,
            Widgets\ResellerRevenueChart::class,
            Widgets\TopResellersWidget::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        // Prevent deletion if reseller has active clients
        if ($record->childCompanies()->count() > 0) {
            return false;
        }
        
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']) ?? false;
    }
}