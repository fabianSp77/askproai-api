<?php

namespace App\Filament\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Resources\KnowledgeBaseArticleResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgeBaseArticle extends ViewRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Artikelinhalt')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->label('Titel'),

                        Components\TextEntry::make('slug')
                            ->label('URL-Slug')
                            ->copyable(),

                        Components\TextEntry::make('summary')
                            ->label('Zusammenfassung')
                            ->columnSpanFull(),

                        Components\TextEntry::make('content')
                            ->label('Inhalt')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Components\Section::make('Klassifizierung')
                    ->schema([
                        Components\TextEntry::make('article_type')
                            ->label('Artikeltyp')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => \App\Models\KnowledgeBaseArticle::ARTICLE_TYPE_LABELS[$state] ?? $state),

                        Components\TextEntry::make('category.name')
                            ->label('Kategorie'),

                        Components\TextEntry::make('keywords')
                            ->label('Schlagwörter')
                            ->badge()
                            ->separator(','),
                    ])
                    ->columns(3),

                Components\Section::make('Status & Analytics')
                    ->schema([
                        Components\IconEntry::make('is_published')
                            ->label('Veröffentlicht')
                            ->boolean(),

                        Components\IconEntry::make('is_featured')
                            ->label('Hervorgehoben')
                            ->boolean(),

                        Components\IconEntry::make('is_internal')
                            ->label('Nur intern')
                            ->boolean(),

                        Components\TextEntry::make('view_count')
                            ->label('Aufrufe')
                            ->numeric(),

                        Components\TextEntry::make('helpful_count')
                            ->label('Hilfreich')
                            ->numeric(),

                        Components\TextEntry::make('not_helpful_count')
                            ->label('Nicht hilfreich')
                            ->numeric(),
                    ])
                    ->columns(6),

                Components\Section::make('Metadaten')
                    ->schema([
                        Components\TextEntry::make('author.name')
                            ->label('Autor'),

                        Components\TextEntry::make('lastReviewedBy.name')
                            ->label('Zuletzt geprüft von'),

                        Components\TextEntry::make('last_reviewed_at')
                            ->label('Zuletzt geprüft am')
                            ->dateTime('d.m.Y H:i'),

                        Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i'),

                        Components\TextEntry::make('updated_at')
                            ->label('Aktualisiert am')
                            ->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(5)
                    ->collapsed(),
            ]);
    }
}
