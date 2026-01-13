<?php

namespace App\Filament\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Resources\KnowledgeBaseArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeBaseArticle extends EditRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('mark_reviewed')
                ->label('Als geprüft markieren')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function () {
                    $this->record->update([
                        'last_reviewed_at' => now(),
                        'last_reviewed_by' => auth()->user()?->staff?->id,
                    ]);
                    $this->refreshFormData(['last_reviewed_at', 'last_reviewed_by']);
                })
                ->requiresConfirmation()
                ->modalHeading('Artikel als geprüft markieren')
                ->modalDescription('Bestätigen Sie, dass dieser Artikel überprüft wurde und aktuell ist.'),
        ];
    }
}
