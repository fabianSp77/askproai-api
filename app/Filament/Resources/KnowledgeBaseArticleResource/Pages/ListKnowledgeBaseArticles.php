<?php

namespace App\Filament\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Resources\KnowledgeBaseArticleResource;
use App\Models\KnowledgeBaseArticle;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseArticles extends ListRecords
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * ServiceNow-style tabs for quick filtering
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->badge(KnowledgeBaseArticle::count()),

            'published' => Tab::make('Veröffentlicht')
                ->modifyQueryUsing(fn ($query) => $query->published())
                ->badge(KnowledgeBaseArticle::published()->count())
                ->badgeColor('success'),

            'draft' => Tab::make('Entwurf')
                ->modifyQueryUsing(fn ($query) => $query->where('is_published', false))
                ->badge(KnowledgeBaseArticle::where('is_published', false)->count())
                ->badgeColor('warning'),

            'featured' => Tab::make('Hervorgehoben')
                ->modifyQueryUsing(fn ($query) => $query->featured())
                ->badge(KnowledgeBaseArticle::featured()->count())
                ->badgeColor('info'),

            'internal' => Tab::make('Nur intern')
                ->modifyQueryUsing(fn ($query) => $query->where('is_internal', true))
                ->badge(KnowledgeBaseArticle::where('is_internal', true)->count())
                ->badgeColor('gray'),
        ];
    }
}
