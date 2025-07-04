<?php

namespace App\Filament\Admin\Resources\SubscriptionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Subscription Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('stripe_price_id')
                    ->label('Price ID')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('stripe_product_id')
                    ->label('Product ID')
                    ->disabled(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('stripe_price_id')
            ->columns([
                Tables\Columns\TextColumn::make('stripe_price_id')
                    ->label('Price ID')
                    ->copyable(),
                Tables\Columns\TextColumn::make('stripe_product_id')
                    ->label('Product ID')
                    ->copyable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Items are managed through Stripe
            ])
            ->actions([
                Tables\Actions\Action::make('view_in_stripe')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => "https://dashboard.stripe.com/products/{$record->stripe_product_id}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}