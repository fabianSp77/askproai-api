<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Filament\Admin\Resources\CallResource\RelationManagers;
use App\Models\Call;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Kommunikation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('call_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('external_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('conversation_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('call_status')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Toggle::make('call_successful'),
                Forms\Components\TextInput::make('retell_call_id')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tmp_call_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('from_number')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('to_number')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('duration_sec')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('analysis')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cost_cents')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('transcript')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('raw')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('kunde_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('customer_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('branch_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('phone_number_id')
                    ->tel()
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('agent_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('details')
                    ->columnSpanFull(),
            ]);
    /** Navigation immer sichtbar */
    public static function canViewAny(): bool
    {
        return true;   // TODO: Policy/Shield nutzen
    }

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('conversation_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('call_status')
                    ->searchable(),
                Tables\Columns\IconColumn::make('call_successful')
                    ->boolean(),
                Tables\Columns\TextColumn::make('retell_call_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tmp_call_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_sec')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_cents')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kunde_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent_id')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
            'create' => Pages\CreateCall::route('/create'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
        ];
    }
}
