<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notizen';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'general' => 'Allgemein',
                                'appointment' => 'Termin',
                                'call' => 'Anruf',
                                'complaint' => 'Beschwerde',
                                'feedback' => 'Feedback',
                                'internal' => 'Intern',
                            ])
                            ->default('general')
                            ->required(),
                        Forms\Components\Select::make('visibility')
                            ->label('Sichtbarkeit')
                            ->options([
                                'public' => 'Öffentlich',
                                'internal' => 'Intern',
                                'management' => 'Management',
                            ])
                            ->default('public')
                            ->required(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Betreff')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->label('Inhalt')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'redo',
                                'undo',
                            ]),
                        Forms\Components\Toggle::make('is_pinned')
                            ->label('Angeheftet')
                            ->helperText('Angeheftete Notizen werden oben angezeigt'),
                        Forms\Components\Toggle::make('is_important')
                            ->label('Wichtig')
                            ->helperText('Als wichtig markierte Notizen werden hervorgehoben'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-bookmark')
                    ->falseIcon(null)
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->weight(fn ($record) => $record->is_important ? 'bold' : 'regular')
                    ->description(fn ($record) => strip_tags(substr($record->content, 0, 100)) . '...')
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'general',
                        'info' => 'appointment',
                        'success' => 'call',
                        'danger' => 'complaint',
                        'warning' => 'feedback',
                        'secondary' => 'internal',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'general' => 'Allgemein',
                        'appointment' => 'Termin',
                        'call' => 'Anruf',
                        'complaint' => 'Beschwerde',
                        'feedback' => 'Feedback',
                        'internal' => 'Intern',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Sichtbarkeit')
                    ->colors([
                        'success' => 'public',
                        'warning' => 'internal',
                        'danger' => 'management',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'public' => 'Öffentlich',
                        'internal' => 'Intern',
                        'management' => 'Management',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Erstellt von')
                    ->getStateUsing(fn ($record) => $record->creator?->name ?? 'System')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_important')
                    ->label('Wichtig')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->trueColor('danger')
                    ->falseIcon(null),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'appointment' => 'Termin',
                        'call' => 'Anruf',
                        'complaint' => 'Beschwerde',
                        'feedback' => 'Feedback',
                        'internal' => 'Intern',
                    ]),
                Tables\Filters\SelectFilter::make('visibility')
                    ->label('Sichtbarkeit')
                    ->options([
                        'public' => 'Öffentlich',
                        'internal' => 'Intern',
                        'management' => 'Management',
                    ]),
                Tables\Filters\TernaryFilter::make('is_important')
                    ->label('Wichtig'),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Angeheftet'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Notiz')
                    ->modalHeading('Neue Notiz anlegen')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->ownerRecord->id;
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen')
                    ->modalHeading('Notiz anzeigen'),
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten'),
                Tables\Actions\Action::make('togglePin')
                    ->label(fn ($record) => $record->is_pinned ? 'Lösen' : 'Anheften')
                    ->icon(fn ($record) => $record->is_pinned ? 'heroicon-o-bookmark-slash' : 'heroicon-o-bookmark')
                    ->action(fn ($record) => $record->update(['is_pinned' => !$record->is_pinned])),
                Tables\Actions\DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen'),
                ]),
            ])
            ->emptyStateHeading('Keine Notizen vorhanden')
            ->emptyStateDescription('Fügen Sie Notizen hinzu, um wichtige Informationen über diesen Kunden zu dokumentieren.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}