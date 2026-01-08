<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeBaseArticleResource\Pages;
use App\Models\KnowledgeBaseArticle;
use App\Models\ServiceCaseCategory;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Knowledge Base Article Resource
 *
 * ServiceNow-style knowledge management for self-service and agent reference.
 * Supports rich text editing, categorization, and analytics.
 */
class KnowledgeBaseArticleResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Wissensdatenbank';
    protected static ?string $modelLabel = 'Artikel';
    protected static ?string $pluralModelLabel = 'Artikel';
    protected static ?int $navigationSort = 17;

    /**
     * Only show when Service Gateway is enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Artikelinhalt')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if ($state) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('summary')
                            ->label('Zusammenfassung')
                            ->helperText('Kurze Beschreibung für Suchergebnisse')
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\RichEditor::make('content')
                            ->label('Inhalt')
                            ->required()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Klassifizierung')
                    ->schema([
                        Forms\Components\Select::make('article_type')
                            ->label('Artikeltyp')
                            ->options(KnowledgeBaseArticle::ARTICLE_TYPE_LABELS)
                            ->default(KnowledgeBaseArticle::TYPE_HOW_TO)
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategorie')
                            ->options(function () {
                                return ServiceCaseCategory::query()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Verknüpfte Service-Kategorie'),

                        Forms\Components\TagsInput::make('keywords')
                            ->label('Schlagwörter')
                            ->placeholder('Schlagwort eingeben')
                            ->helperText('Für bessere Suchergebnisse')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Veröffentlichung')
                    ->description('Steuert wer den Artikel sehen kann')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Veröffentlicht')
                            ->helperText('Hauptschalter: Artikel für Benutzer sichtbar machen')
                            ->default(false)
                            ->live()
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_internal')
                            ->label('Nur intern')
                            ->helperText('Nur für eingeloggte Mitarbeiter sichtbar (nicht öffentlich)')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('is_published')),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Hervorgehoben')
                            ->helperText('Prominent auf der Startseite anzeigen')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('is_published')),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sortierung')
                            ->numeric()
                            ->default(0)
                            ->helperText('Niedrigere Werte = weiter oben'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('author_id')
                            ->label('Autor')
                            ->options(function () {
                                return Staff::query()->pluck('name', 'id');
                            })
                            ->default(fn () => Auth::user()?->staff?->id)
                            ->searchable(),

                        Forms\Components\Placeholder::make('last_reviewed_info')
                            ->label('Letzte Überprüfung')
                            ->content(function (?KnowledgeBaseArticle $record) {
                                if (!$record?->last_reviewed_at) {
                                    return 'Noch nicht überprüft';
                                }
                                $reviewer = $record->lastReviewedBy?->name ?? 'Unbekannt';
                                return "{$record->last_reviewed_at->format('d.m.Y H:i')} von {$reviewer}";
                            }),
                    ])
                    ->columns(2)
                    ->collapsed(),
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
                    ->limit(50),

                Tables\Columns\TextColumn::make('article_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => KnowledgeBaseArticle::ARTICLE_TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'how_to' => 'info',
                        'faq' => 'success',
                        'reference' => 'gray',
                        'troubleshooting' => 'warning',
                        'policy' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Veröffentlicht')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('Aufrufe')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('helpfulness')
                    ->label('Hilfreich')
                    ->state(function (KnowledgeBaseArticle $record) {
                        $percentage = $record->helpfulness_percentage;
                        if ($percentage === null) {
                            return '—';
                        }
                        return "{$percentage}%";
                    })
                    ->color(fn (KnowledgeBaseArticle $record) => match (true) {
                        $record->helpfulness_percentage === null => 'gray',
                        $record->helpfulness_percentage >= 80 => 'success',
                        $record->helpfulness_percentage >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autor')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('article_type')
                    ->label('Artikeltyp')
                    ->options(KnowledgeBaseArticle::ARTICLE_TYPE_LABELS),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Veröffentlicht'),

                Tables\Filters\TernaryFilter::make('is_internal')
                    ->label('Nur intern'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Veröffentlichen')
                        ->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Zurückziehen')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn ($records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeBaseArticles::route('/'),
            'create' => Pages\CreateKnowledgeBaseArticle::route('/create'),
            'view' => Pages\ViewKnowledgeBaseArticle::route('/{record}'),
            'edit' => Pages\EditKnowledgeBaseArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'author']);
    }
}
