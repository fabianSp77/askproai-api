<?php

namespace App\Filament\Admin\Resources\UltimateCallResource\Pages;

use App\Filament\Admin\Resources\UltimateCallResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;

class CreateCall extends CreateRecord
{
    protected static string $resource = UltimateCallResource::class;

    protected static ?string $title = 'New Call Record';

    protected static string $view = 'filament.admin.pages.ultra-call-create';

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
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->required(),
                                    Forms\Components\TextInput::make('phone')->required(),
                                    Forms\Components\TextInput::make('email')->email(),
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('phone_number')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->placeholder('+49 123 456789'),

                            Forms\Components\Select::make('call_type')
                                ->label('Call Type')
                                ->options([
                                    'inbound' => 'Inbound',
                                    'outbound' => 'Outbound',
                                    'internal' => 'Internal',
                                ])
                                ->default('inbound')
                                ->required(),

                            Forms\Components\Select::make('status')
                                ->label('Call Status')
                                ->options([
                                    'active' => 'Active',
                                    'completed' => 'Completed',
                                    'missed' => 'Missed',
                                    'abandoned' => 'Abandoned',
                                ])
                                ->default('active')
                                ->required(),
                        ]),
                    ]),

                Section::make('Call Details')
                    ->description('Additional call information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('started_at')
                                ->label('Start Time')
                                ->default(now())
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
                                ->label('Duration (seconds)')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\Select::make('sentiment')
                                ->label('Call Sentiment')
                                ->options([
                                    'positive' => 'Positive',
                                    'neutral' => 'Neutral',
                                    'negative' => 'Negative',
                                ])
                                ->default('neutral'),

                            Forms\Components\TextInput::make('sentiment_score')
                                ->label('Sentiment Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10)
                                ->step(0.1)
                                ->placeholder('0.0 - 10.0'),

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
                                'redo',
                                'undo',
                            ]),

                        Forms\Components\Textarea::make('transcript')
                            ->label('Call Transcript')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('tags')
                            ->label('Tags')
                            ->keyLabel('Tag')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Tag')
                            ->columnSpanFull(),
                    ]),

                Section::make('Recording')
                    ->description('Call recording information')
                    ->icon('heroicon-o-microphone')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('recording_url')
                            ->label('Recording URL')
                            ->url()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('has_recording')
                            ->label('Has Recording')
                            ->reactive(),
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('company_id');
        
        // Set default sentiment score if sentiment is set but score is not
        if (isset($data['sentiment']) && !isset($data['sentiment_score'])) {
            $data['sentiment_score'] = match($data['sentiment']) {
                'positive' => 8.0,
                'neutral' => 5.0,
                'negative' => 2.0,
                default => 5.0,
            };
        }

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Call recorded')
            ->body('The call has been successfully recorded.')
            ->icon('heroicon-o-phone')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}