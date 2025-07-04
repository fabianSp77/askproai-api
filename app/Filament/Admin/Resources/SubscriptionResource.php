<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Filament\Admin\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationLabel = 'Abonnements';
    
    protected static ?string $navigationGroup = 'Abrechnung';
    
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\Select::make('pricing_plan_id')
                    ->relationship('pricingPlan', 'name'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('stripe_subscription_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('stripe_customer_id')
                    ->maxLength(255),
                Forms\Components\Select::make('stripe_status')
                    ->options([
                        'active' => 'Aktiv',
                        'past_due' => 'Überfällig',
                        'unpaid' => 'Unbezahlt',
                        'canceled' => 'Gekündigt',
                        'incomplete' => 'Unvollständig',
                        'incomplete_expired' => 'Unvollständig abgelaufen',
                        'trialing' => 'Testphase',
                        'paused' => 'Pausiert',
                    ]),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1),
                Forms\Components\Select::make('billing_interval')
                    ->options([
                        'day' => 'Täglich',
                        'week' => 'Wöchentlich',
                        'month' => 'Monatlich',
                        'year' => 'Jährlich',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('billing_interval_count')
                    ->numeric()
                    ->default(1),
                Forms\Components\DateTimePicker::make('trial_ends_at'),
                Forms\Components\DateTimePicker::make('ends_at'),
                Forms\Components\DatePicker::make('next_billing_date'),
                Forms\Components\Toggle::make('cancel_at_period_end')
                    ->label('Am Periodenende kündigen'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pricingPlan.name')
                    ->label('Tarif')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('stripe_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => fn ($state) => in_array($state, ['past_due', 'unpaid']),
                        'danger' => fn ($state) => in_array($state, ['canceled', 'incomplete_expired']),
                        'primary' => 'trialing',
                        'secondary' => fn ($state) => in_array($state, ['incomplete', 'paused']),
                    ]),
                Tables\Columns\TextColumn::make('billing_interval')
                    ->label('Abrechnungsintervall')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'day' => 'Täglich',
                        'week' => 'Wöchentlich',
                        'month' => 'Monatlich',
                        'year' => 'Jährlich',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label('Nächste Abrechnung')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('cancel_at_period_end')
                    ->label('Kündigung')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'past_due' => 'Überfällig',
                        'unpaid' => 'Unbezahlt',
                        'canceled' => 'Gekündigt',
                        'trialing' => 'Testphase',
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
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}