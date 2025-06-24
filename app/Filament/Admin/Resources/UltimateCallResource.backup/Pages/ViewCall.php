<?php

namespace App\Filament\Admin\Resources\UltimateCallResource\Pages;

use App\Filament\Admin\Resources\UltimateCallResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewCall extends ViewRecord
{
    protected static string $resource = UltimateCallResource::class;

    protected static string $view = 'filament.admin.pages.ultra-call-view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('play_recording')
                ->label('Play Recording')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->size('lg')
                ->visible(fn () => $this->record->recording_url)
                ->modalContent(view('filament.modals.audio-player', [
                    'url' => $this->record->recording_url ?? '',
                ]))
                ->modalHeading('Call Recording')
                ->modalSubmitAction(false),

            Actions\Action::make('download_transcript')
                ->label('Download Transcript')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->record->transcript)
                ->action(function () {
                    return response()->streamDownload(
                        function () {
                            echo $this->record->transcript;
                        },
                        "call-transcript-{$this->record->id}.txt"
                    );
                }),

            Actions\Action::make('share')
                ->label('Share')
                ->icon('heroicon-o-share')
                ->color('gray')
                ->modalContent(view('filament.modals.share-call', [
                    'call' => $this->record,
                ]))
                ->modalHeading('Share Call Details')
                ->modalSubmitAction(false),

            Actions\EditAction::make()
                ->size('lg'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Call Overview')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('phone_number')
                                    ->label('Phone Number')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('call_type')
                                    ->label('Type')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'inbound' => 'primary',
                                        'outbound' => 'success',
                                        'internal' => 'gray',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'active' => 'warning',
                                        'completed' => 'success',
                                        'missed' => 'danger',
                                        'abandoned' => 'gray',
                                        default => 'gray',
                                    }),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('duration')
                                    ->label('Duration')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : '00:00:00')
                                    ->icon('heroicon-o-clock')
                                    ->size('lg')
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('staff.name')
                                    ->label('Handled By')
                                    ->icon('heroicon-o-user-circle')
                                    ->default('Unassigned'),
                            ]),
                        ]),
                    ]),

                Infolists\Components\Section::make('Call Timeline')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('started_at')
                                ->label('Started')
                                ->dateTime()
                                ->icon('heroicon-o-play'),
                            Infolists\Components\TextEntry::make('ended_at')
                                ->label('Ended')
                                ->dateTime()
                                ->icon('heroicon-o-stop')
                                ->default('Ongoing'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Sentiment Analysis')
                    ->icon('heroicon-o-face-smile')
                    ->visible(fn () => $this->record->sentiment)
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('sentiment')
                                ->label('Sentiment')
                                ->badge()
                                ->size('lg')
                                ->color(fn ($state) => match($state) {
                                    'positive' => 'success',
                                    'neutral' => 'gray',
                                    'negative' => 'danger',
                                    default => 'gray',
                                })
                                ->icon(fn ($state) => match($state) {
                                    'positive' => 'heroicon-o-face-smile',
                                    'neutral' => 'heroicon-o-minus-circle',
                                    'negative' => 'heroicon-o-face-frown',
                                    default => 'heroicon-o-question-mark-circle',
                                }),
                            Infolists\Components\TextEntry::make('sentiment_score')
                                ->label('Score')
                                ->formatStateUsing(fn ($state) => $state ? "{$state}/10" : 'N/A')
                                ->icon('heroicon-o-chart-bar')
                                ->color(fn ($state) => 
                                    $state >= 7 ? 'success' : 
                                    ($state >= 4 ? 'gray' : 'danger')
                                ),
                        ]),

                        Infolists\Components\ViewEntry::make('sentiment_chart')
                            ->label('Sentiment Breakdown')
                            ->view('filament.admin.components.sentiment-chart')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Call Content')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->html()
                            ->columnSpanFull()
                            ->default('No notes recorded'),

                        Infolists\Components\TextEntry::make('transcript')
                            ->label('Transcript')
                            ->prose()
                            ->columnSpanFull()
                            ->default('No transcript available')
                            ->extraAttributes(['class' => 'max-h-96 overflow-y-auto']),
                    ]),

                Infolists\Components\Section::make('Tags & Metadata')
                    ->icon('heroicon-o-tag')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('tags')
                            ->label('Tags')
                            ->columnSpanFull()
                            ->default([]),

                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('id')
                                ->label('Call ID')
                                ->copyable(),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created')
                                ->since(),
                            Infolists\Components\TextEntry::make('updated_at')
                                ->label('Updated')
                                ->since(),
                            Infolists\Components\IconEntry::make('has_recording')
                                ->label('Recording')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Related Information')
                    ->icon('heroicon-o-link')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\ViewEntry::make('customer_history')
                                ->label('Customer Call History')
                                ->view('filament.admin.components.customer-call-history')
                                ->columnSpan(1),

                            Infolists\Components\ViewEntry::make('related_appointments')
                                ->label('Related Appointments')
                                ->view('filament.admin.components.related-appointments')
                                ->columnSpan(1),
                        ]),
                    ]),
            ]);
    }
}