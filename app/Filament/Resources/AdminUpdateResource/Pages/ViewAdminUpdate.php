<?php

namespace App\Filament\Resources\AdminUpdateResource\Pages;

use App\Filament\Resources\AdminUpdateResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewAdminUpdate extends ViewRecord
{
    protected static string $resource = AdminUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('✏️ Bearbeiten'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Update Information')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Titel')
                            ->copyable()
                            ->size('lg')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('category')
                            ->label('Kategorie')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'bugfix' => '🐛 Bugfix',
                                'improvement' => '⚡ Verbesserung',
                                'feature' => '✨ Feature',
                                default => '📋 Allgemein',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'bugfix' => 'danger',
                                'improvement' => 'success',
                                'feature' => 'primary',
                                default => 'secondary',
                            }),

                        Infolists\Components\TextEntry::make('priority')
                            ->label('Priorität')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'critical' => '🔴 Kritisch',
                                'high' => '🟠 Hoch',
                                'medium' => '🟡 Mittel',
                                'low' => '🟢 Niedrig',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                'low' => 'success',
                            }),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'draft' => '📝 Entwurf',
                                'published' => '✅ Veröffentlicht',
                                'archived' => '📦 Archiviert',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'draft' => 'secondary',
                                'published' => 'success',
                                'archived' => 'gray',
                            }),
                    ]),

                Infolists\Components\Section::make('Beschreibung')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('')
                            ->html()
                            ->state(fn ($record) => nl2br(e($record->description)))
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Detaillierter Inhalt')
                    ->schema([
                        Infolists\Components\TextEntry::make('content')
                            ->label('')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Code Snippets - Zum Kopieren')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('code_snippets')
                            ->label('')
                            ->state(fn ($record) => $record->code_snippets ? json_encode($record->code_snippets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'Keine Code Snippets')
                            ->copyable(fn ($record) => $record->code_snippets ? true : false)
                            ->copyableState(fn ($record) => json_encode($record->code_snippets, JSON_PRETTY_PRINT))
                            ->columnSpanFull()
                            ->html(false),
                    ]),

                Infolists\Components\Section::make('Betroffene Dateien')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('related_files')
                            ->label('')
                            ->copyable()
                            ->state(fn ($record) => $record->related_files ?: 'Keine')
                            ->columnSpanFull()
                            ->html(false),
                    ]),

                Infolists\Components\Section::make('Action Items')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('action_items')
                            ->label('')
                            ->state(fn ($record) => $record->action_items ? json_encode($record->action_items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'Keine Action Items')
                            ->copyable(fn ($record) => $record->action_items ? true : false)
                            ->copyableState(fn ($record) => json_encode($record->action_items, JSON_PRETTY_PRINT))
                            ->columnSpanFull()
                            ->html(false),
                    ]),

                Infolists\Components\Section::make('Tracking Information')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('related_issue')
                            ->label('Related Issue')
                            ->copyable()
                            ->state(fn ($record) => $record->related_issue ?: '—'),

                        Infolists\Components\TextEntry::make('creator.email')
                            ->label('Erstellt von'),

                        Infolists\Components\TextEntry::make('published_at')
                            ->label('Veröffentlicht')
                            ->dateTime('d.m.Y H:i')
                            ->state(fn ($record) => $record->published_at ?: '—'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i'),
                    ]),

                Infolists\Components\Section::make('Changelog')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('changelog')
                            ->label('')
                            ->copyable()
                            ->state(fn ($record) => $record->changelog ?: 'Kein Changelog')
                            ->columnSpanFull()
                            ->html(false),
                    ]),
            ]);
    }

    public function getTitle(): string
    {
        return $this->record->title;
    }

    public function getSubHeading(): string
    {
        return 'Kategorie: ' . $this->record->getCategoryLabel()
            . ' | Priorität: ' . $this->record->getPriorityLabel()
            . ' | Status: ' . ucfirst($this->record->status);
    }
}
