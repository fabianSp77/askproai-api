<?php

namespace App\Filament\Admin\Resources\UltimateCallResource\Pages;

use App\Filament\Admin\Resources\UltimateCallResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditCall extends EditRecord
{
    protected static string $resource = UltimateCallResource::class;

    protected static string $view = 'filament.admin.pages.ultra-call-edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('play_recording')
                ->label('Play Recording')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->record->recording_url)
                ->modalContent(view('filament.modals.audio-player', [
                    'url' => $this->record->recording_url ?? '',
                ]))
                ->modalHeading('Call Recording')
                ->modalSubmitAction(false),

            Actions\Action::make('analyze_sentiment')
                ->label('Analyze Sentiment')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(function () {
                    // Simulate sentiment analysis
                    $sentiments = ['positive', 'neutral', 'negative'];
                    $sentiment = $sentiments[array_rand($sentiments)];
                    $score = match($sentiment) {
                        'positive' => rand(70, 100) / 10,
                        'neutral' => rand(40, 60) / 10,
                        'negative' => rand(10, 30) / 10,
                    };

                    $this->record->update([
                        'sentiment' => $sentiment,
                        'sentiment_score' => $score,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Sentiment Analyzed')
                        ->body("Call sentiment: {$sentiment} (Score: {$score}/10)")
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Call Record')
                ->modalDescription('Are you sure you want to delete this call record? This action cannot be undone.'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Call Information')
                    ->description('Basic call details')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('phone_number')
                                ->label('Phone Number')
                                ->tel()
                                ->required(),

                            Forms\Components\Select::make('call_type')
                                ->label('Call Type')
                                ->options([
                                    'inbound' => 'Inbound',
                                    'outbound' => 'Outbound',
                                    'internal' => 'Internal',
                                ])
                                ->required(),

                            Forms\Components\Select::make('status')
                                ->label('Call Status')
                                ->options([
                                    'active' => 'Active',
                                    'completed' => 'Completed',
                                    'missed' => 'Missed',
                                    'abandoned' => 'Abandoned',
                                ])
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state) {
                                    if ($state === 'completed' && !$this->record->ended_at) {
                                        $this->record->update(['ended_at' => now()]);
                                    }
                                }),
                        ]),
                    ]),

                Section::make('Call Details')
                    ->description('Additional call information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('started_at')
                                ->label('Start Time')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => 
                                    $this->calculateDuration($state, $get('ended_at'), $set)
                                ),

                            Forms\Components\DateTimePicker::make('ended_at')
                                ->label('End Time')
                                ->minDate(fn (Forms\Get $get) => $get('started_at'))
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => 
                                    $this->calculateDuration($get('started_at'), $state, $set)
                                ),

                            Forms\Components\TextInput::make('duration')
                                ->label('Duration')
                                ->disabled()
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : '00:00:00'),

                            Forms\Components\Select::make('sentiment')
                                ->label('Call Sentiment')
                                ->options([
                                    'positive' => 'Positive',
                                    'neutral' => 'Neutral',
                                    'negative' => 'Negative',
                                ])
                                ->reactive(),

                            Forms\Components\TextInput::make('sentiment_score')
                                ->label('Sentiment Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10)
                                ->step(0.1)
                                ->suffix('/10'),

                            Forms\Components\Select::make('staff_id')
                                ->label('Handled By')
                                ->relationship('staff', 'name')
                                ->searchable()
                                ->preload(),
                        ]),

                        Forms\Components\RichEditor::make('notes')
                            ->label('Call Notes')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'link',
                                'redo',
                                'undo',
                            ]),

                        Forms\Components\Textarea::make('transcript')
                            ->label('Call Transcript')
                            ->rows(8)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('tags')
                            ->label('Tags')
                            ->keyLabel('Tag')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Tag')
                            ->columnSpanFull()
                            ->default([]),
                    ]),

                Section::make('Recording & Analytics')
                    ->description('Call recording and analytics data')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('recording_url')
                                ->label('Recording URL')
                                ->url()
                                ->columnSpan(2),

                            Forms\Components\Toggle::make('has_recording')
                                ->label('Has Recording')
                                ->disabled(),

                            Forms\Components\Toggle::make('is_analyzed')
                                ->label('Sentiment Analyzed')
                                ->disabled(),
                        ]),

                        Forms\Components\Placeholder::make('analytics')
                            ->label('Call Analytics')
                            ->content(function () {
                                if (!$this->record->sentiment) {
                                    return 'No analytics available. Click "Analyze Sentiment" to generate.';
                                }

                                return view('filament.admin.components.call-analytics', [
                                    'call' => $this->record,
                                ]);
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadata')
                    ->description('System information')
                    ->icon('heroicon-o-cog')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Placeholder::make('created_at')
                                ->label('Created')
                                ->content(fn () => $this->record->created_at->diffForHumans()),

                            Forms\Components\Placeholder::make('updated_at')
                                ->label('Last Updated')
                                ->content(fn () => $this->record->updated_at->diffForHumans()),

                            Forms\Components\Placeholder::make('call_id')
                                ->label('Call ID')
                                ->content(fn () => $this->record->id),

                            Forms\Components\Placeholder::make('company')
                                ->label('Company')
                                ->content(fn () => $this->record->company->name ?? 'N/A'),
                        ]),
                    ]),
            ]);
    }

    protected function calculateDuration($startedAt, $endedAt, Forms\Set $set): void
    {
        if ($startedAt && $endedAt) {
            $start = \Carbon\Carbon::parse($startedAt);
            $end = \Carbon\Carbon::parse($endedAt);
            
            if ($end->gt($start)) {
                $duration = $end->diffInSeconds($start);
                $set('duration', $duration);
            }
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Call updated')
            ->body('The call record has been successfully updated.')
            ->icon('heroicon-o-check-circle')
            ->send();
    }
}