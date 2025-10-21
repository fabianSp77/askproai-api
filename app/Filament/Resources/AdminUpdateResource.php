<?php

namespace App\Filament\Resources;

use App\Models\AdminUpdate;
use App\Models\User;
use App\Filament\Resources\AdminUpdateResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AdminUpdateResource extends Resource
{
    protected static ?string $model = AdminUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = '📋 Admin Updates Portal';

    protected static ?string $modelLabel = 'Admin Update';

    protected static ?string $pluralModelLabel = 'Admin Updates';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = '⚙️ System Administration';

    /**
     * Only Super-Admin can access this resource
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->email === 'fabian@askpro.de'
            || ($user->is_super_admin ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Update Information')
                    ->description('Grundinformationen für das Admin Update')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. "Email Collection Bug Fix"'),

                        Forms\Components\Textarea::make('description')
                            ->label('Kurzbeschreibung')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Kurze Zusammenfassung des Updates'),

                        Group::make()
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Kategorie')
                                    ->options([
                                        'bugfix' => '🐛 Bugfix',
                                        'improvement' => '⚡ Verbesserung',
                                        'feature' => '✨ Feature',
                                        'general' => '📋 Allgemein',
                                    ])
                                    ->default('general')
                                    ->required(),

                                Forms\Components\Select::make('priority')
                                    ->label('Priorität')
                                    ->options([
                                        'critical' => '🔴 Kritisch',
                                        'high' => '🟠 Hoch',
                                        'medium' => '🟡 Mittel',
                                        'low' => '🟢 Niedrig',
                                    ])
                                    ->default('medium')
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1),

                Section::make('Detaillierter Inhalt')
                    ->description('Vollständiger Inhalt des Updates (HTML/Markdown)')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Content')
                            ->required()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'heading',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strikethrough',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Code Snippets & Attachments')
                    ->description('Copy-Paste ready Code und Dateien')
                    ->schema([
                        Forms\Components\Textarea::make('code_snippets')
                            ->label('Code Snippets (JSON)')
                            ->rows(6)
                            ->hint('JSON array mit Code-Blöcken')
                            ->placeholder('[{"title":"Snippet 1","code":"..."}]')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('related_files')
                            ->label('Related Files')
                            ->rows(4)
                            ->placeholder('app/Services/Retell/DateTimeParser.php
app/Http/Controllers/RetellFunctionCallHandler.php')
                            ->columnSpanFull(),
                    ]),

                Section::make('Action Items & Tracking')
                    ->description('TODO items und Tracking-Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('action_items')
                            ->label('Action Items (JSON)')
                            ->rows(4)
                            ->hint('JSON array mit TODO-Items')
                            ->placeholder('[{"task":"Fix X","status":"pending"},...]')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('related_issue')
                            ->label('Related Issue/Ticket')
                            ->placeholder('z.B. JIRA-123 oder GitHub Issue #456'),
                    ]),

                Section::make('Veröffentlichung')
                    ->description('Status und Sichtbarkeit')
                    ->schema([
                        Group::make()
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => '📝 Entwurf',
                                        'published' => '✅ Veröffentlicht',
                                        'archived' => '📦 Archiviert',
                                    ])
                                    ->default('draft')
                                    ->required(),

                                Forms\Components\DatePicker::make('published_at')
                                    ->label('Veröffentlicht am')
                                    ->format('d.m.Y'),

                                Forms\Components\Toggle::make('is_public')
                                    ->label('Öffentlich sichtbar?')
                                    ->default(false)
                                    ->helperText('Normalerweise: nein (nur Admin)'),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('changelog')
                            ->label('Changelog / Edit History')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (AdminUpdate $record) => AdminUpdateResource::getUrl('view', ['record' => $record->id]))
                    ->openUrlInNewTab(false),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'bugfix' => '🐛 Bugfix',
                        'improvement' => '⚡ Verbesserung',
                        'feature' => '✨ Feature',
                        default => '📋 Allgemein',
                    })
                    ->colors([
                        'danger' => 'bugfix',
                        'success' => 'improvement',
                        'primary' => 'feature',
                        'secondary' => 'general',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'critical' => '🔴 Kritisch',
                        'high' => '🟠 Hoch',
                        'medium' => '🟡 Mittel',
                        'low' => '🟢 Niedrig',
                    })
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'high',
                        'info' => 'medium',
                        'success' => 'low',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => '📝 Entwurf',
                        'published' => '✅ Veröffentlicht',
                        'archived' => '📦 Archiviert',
                    })
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'published',
                        'gray' => 'archived',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.email')
                    ->label('Erstellt von')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Veröffentlicht')
                    ->date('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->date('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'bugfix' => '🐛 Bugfix',
                        'improvement' => '⚡ Verbesserung',
                        'feature' => '✨ Feature',
                        'general' => '📋 Allgemein',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'critical' => '🔴 Kritisch',
                        'high' => '🟠 Hoch',
                        'medium' => '🟡 Mittel',
                        'low' => '🟢 Niedrig',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => '📝 Entwurf',
                        'published' => '✅ Veröffentlicht',
                        'archived' => '📦 Archiviert',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️ Lesen')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('✏️ Bearbeiten'),
                Tables\Actions\DeleteAction::make()
                    ->label('🗑️ Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUpdates::route('/'),
            'create' => Pages\CreateAdminUpdate::route('/create'),
            'view' => Pages\ViewAdminUpdate::route('/{record}'),
            'edit' => Pages\EditAdminUpdate::route('/{record}/edit'),
        ];
    }
}
