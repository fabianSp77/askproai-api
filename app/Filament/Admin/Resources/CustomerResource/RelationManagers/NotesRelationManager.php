<?php

namespace App\Filament\Admin\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';
    protected static ?string $title = 'Notizen';
    protected static ?string $modelLabel = 'Notiz';
    protected static ?string $pluralModelLabel = 'Notizen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->label('Inhalt')
                    ->required()
                    ->rows(5)
                    ->maxLength(65535),
                Forms\Components\Select::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                        'medical' => 'Medizinisch',
                        'preference' => 'Präferenz',
                        'complaint' => 'Beschwerde',
                    ])
                    ->default('general')
                    ->required(),
                Forms\Components\Toggle::make('is_pinned')
                    ->label('Angeheftet')
                    ->helperText('Angeheftete Notizen werden oben angezeigt'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'gray' => 'general',
                        'danger' => 'important',
                        'info' => 'medical',
                        'success' => 'preference',
                        'warning' => 'complaint',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                        'medical' => 'Medizinisch',
                        'preference' => 'Präferenz',
                        'complaint' => 'Beschwerde',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Angeheftet')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Erstellt von')
                    ->placeholder('System'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                        'medical' => 'Medizinisch',
                        'preference' => 'Präferenz',
                        'complaint' => 'Beschwerde',
                    ]),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Angeheftet')
                    ->placeholder('Alle')
                    ->trueLabel('Nur angeheftete')
                    ->falseLabel('Nur nicht angeheftete'),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Notiz')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_pin')
                    ->label(fn ($record) => $record->is_pinned ? 'Lösen' : 'Anheften')
                    ->icon(fn ($record) => $record->is_pinned ? 'heroicon-m-star' : 'heroicon-o-star')
                    ->action(fn ($record) => $record->update(['is_pinned' => !$record->is_pinned])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}