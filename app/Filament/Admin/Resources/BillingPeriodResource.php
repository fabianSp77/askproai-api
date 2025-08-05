<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BillingPeriodResource\Pages;
use App\Filament\Admin\Resources\BillingPeriodResource\RelationManagers;
use App\Models\BillingPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingPeriodResource extends Resource
{
    protected static ?string $model = BillingPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = null;
    
    protected static ?string $navigationGroup = null;
    
    public static function getNavigationLabel(): string
    {
        return __('admin.resources.billing_periods');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.financial');
    }
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Start')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Ende')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'active' => 'Aktiv',
                        'closed' => 'Abgeschlossen',
                        'invoiced' => 'Abgerechnet',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('total_minutes')
                    ->numeric(),
                Forms\Components\TextInput::make('used_minutes')
                    ->numeric(),
                Forms\Components\TextInput::make('base_fee')
                    ->numeric()
                    ->prefix('�'),
                Forms\Components\TextInput::make('total_cost')
                    ->numeric()
                    ->prefix('�'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ende')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'active',
                        'warning' => 'closed',
                        'primary' => 'invoiced',
                    ]),
                Tables\Columns\TextColumn::make('used_minutes')
                    ->label('Genutzte Minuten')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Gesamtkosten')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'active' => 'Aktiv',
                        'closed' => 'Abgeschlossen',
                        'invoiced' => 'Abgerechnet',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\CallsRelationManager::class, // Removed - not a true DB relationship
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillingPeriods::route('/'),
            'create' => Pages\CreateBillingPeriod::route('/create'),
            'view' => Pages\ViewBillingPeriod::route('/{record}'),
            'edit' => Pages\EditBillingPeriod::route('/{record}/edit'),
        ];
    }
}