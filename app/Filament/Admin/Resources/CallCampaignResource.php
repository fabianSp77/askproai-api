<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallCampaignResource\Pages;
use App\Models\RetellAICallCampaign;
use Filament\Forms;
use Filament\Forms\Form;

use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class CallCampaignResource extends BaseResource
{
    protected static ?string $model = RetellAICallCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';
    
    protected static ?string $navigationGroupKey = 'ai_telephony';
    
    protected static ?string $navigationLabel = 'Outbound Kampagnen';
    
    protected static ?string $modelLabel = 'Kampagne';
    
    protected static ?string $pluralModelLabel = 'Kampagnen';
    
    protected static ?int $navigationSort = 430;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        $company = $user->company ?? $user->tenant?->company;
        
        // Allow if user has admin role or permission
        // Check role names case-insensitively
        $userRoles = $user->roles->pluck('name')->map(function ($role) {
            return strtolower(str_replace(' ', '_', $role));
        })->toArray();
        
        $allowedRoles = ['super_admin', 'admin', 'owner'];
        if (count(array_intersect($userRoles, $allowedRoles)) > 0) {
            return true;
        }
        
        // Check if user can view model (using Laravel policies)
        try {
            if ($user->can('viewAny', static::getModel())) {
                return true;
            }
        } catch (\Exception $e) {
            // If permission doesn't exist, continue to next check
        }
        
        // Check if company can make outbound calls (optional)
        return $company && 
               property_exists($company, 'can_make_outbound_calls') && 
               $company->can_make_outbound_calls;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Kampagnen Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Kampagnen Name')
                            ->required()
                            ->maxLength(255)
                            ->minLength(3)
                            ->regex('/^[a-zA-Z0-9\s\-_äöüÄÖÜß]+$/')
                            ->helperText('Nur Buchstaben, Zahlen, Leerzeichen und Bindestriche erlaubt'),
                            
                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3),
                            
                        TextInput::make('agent_id')
                            ->label('Retell AI Agent ID')
                            ->required()
                            ->default('default-agent')
                            ->helperText('Agent ID für diese Kampagne (z.B. "default-agent")'),
                    ])
                    ->columns(1),

                Section::make('Zielgruppe')
                    ->schema([
                        Select::make('target_type')
                            ->label('Zielgruppen Typ')
                            ->options([
                                'leads' => 'Sales Leads',
                                'appointments' => 'Terminbestätigungen',
                                'follow_up' => 'Nachfass-Anrufe',
                                'survey' => 'Umfragen',
                                'custom_list' => 'Eigene Liste (CSV)'
                            ])
                            ->required()
                            ->reactive(),
                            
                        FileUpload::make('target_list')
                            ->label('CSV Datei hochladen')
                            ->acceptedFileTypes(['text/csv', 'application/csv'])
                            ->visible(fn (callable $get) => $get('target_type') === 'custom_list')
                            ->helperText('CSV Format: Name, Telefonnummer, Zusatzdaten (optional)'),
                            
                        KeyValue::make('target_criteria')
                            ->label('Filter Kriterien')
                            ->visible(fn (callable $get) => in_array($get('target_type'), ['leads', 'appointments', 'follow_up']))
                            ->helperText('Definieren Sie Filter für die automatische Zielgruppenauswahl'),
                    ]),

                Section::make('Zeitplanung')
                    ->schema([
                        Select::make('schedule_type')
                            ->label('Zeitplan')
                            ->options([
                                'immediate' => 'Sofort starten',
                                'scheduled' => 'Geplant',
                                'recurring' => 'Wiederkehrend'
                            ])
                            ->default('immediate')
                            ->reactive(),
                            
                        DateTimePicker::make('scheduled_at')
                            ->label('Startzeit')
                            ->visible(fn (callable $get) => in_array($get('schedule_type'), ['scheduled', 'recurring']))
                            ->required(fn (callable $get) => in_array($get('schedule_type'), ['scheduled', 'recurring'])),
                            
                        KeyValue::make('dynamic_variables')
                            ->label('Dynamische Variablen')
                            ->helperText('Variablen die im Gespräch verwendet werden können'),
                    ])
                    ->columns(2),

                Section::make('Einstellungen')
                    ->schema([
                        TextInput::make('max_concurrent_calls')
                            ->label('Max. gleichzeitige Anrufe')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(20)
                            ->required(),
                            
                        TextInput::make('retry_attempts')
                            ->label('Wiederholungsversuche')
                            ->numeric()
                            ->default(2)
                            ->minValue(0)
                            ->maxValue(5)
                            ->required(),
                            
                        Toggle::make('respect_business_hours')
                            ->label('Nur während Geschäftszeiten anrufen')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kampagne')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'primary' => 'running',
                        'info' => 'paused',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                    
                TextColumn::make('target_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'leads' => 'Sales Leads',
                        'appointments' => 'Termine',
                        'follow_up' => 'Nachfass',
                        'survey' => 'Umfrage',
                        'custom_list' => 'Eigene Liste',
                        default => ucfirst($state)
                    }),
                    
                TextColumn::make('total_targets')
                    ->label('Ziele')
                    ->alignCenter(),
                    
                TextColumn::make('calls_completed')
                    ->label('Abgeschlossen')
                    ->alignCenter()
                    ->color('success'),
                    
                TextColumn::make('calls_failed')
                    ->label('Fehlgeschlagen')
                    ->alignCenter()
                    ->color('danger'),
                    
                TextColumn::make('completion_percentage')
                    ->label('Fortschritt')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->alignCenter(),
                    
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Entwurf',
                        'scheduled' => 'Geplant',
                        'running' => 'Läuft',
                        'paused' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                    ]),
                    
                Tables\Filters\SelectFilter::make('target_type')
                    ->label('Kampagnen Typ')
                    ->options([
                        'leads' => 'Sales Leads',
                        'appointments' => 'Termine',
                        'follow_up' => 'Nachfass',
                        'survey' => 'Umfrage',
                        'custom_list' => 'Eigene Liste',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (RetellAICallCampaign $record) => in_array($record->status, ['draft', 'paused'])),
                    
                Action::make('start')
                    ->label('Starten')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (RetellAICallCampaign $record) => $record->canStart())
                    ->requiresConfirmation()
                    ->action(fn (RetellAICallCampaign $record) => $record->update(['status' => 'running', 'started_at' => now()]))
                    ->after(fn () => Notification::make()->success()->title('Kampagne gestartet')->send()),
                    
                Action::make('pause')
                    ->label('Pausieren')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (RetellAICallCampaign $record) => $record->canPause())
                    ->action(fn (RetellAICallCampaign $record) => $record->update(['status' => 'paused']))
                    ->after(fn () => Notification::make()->success()->title('Kampagne pausiert')->send()),
                    
                Action::make('resume')
                    ->label('Fortsetzen')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (RetellAICallCampaign $record) => $record->canResume())
                    ->action(fn (RetellAICallCampaign $record) => $record->update(['status' => 'running']))
                    ->after(fn () => Notification::make()->success()->title('Kampagne fortgesetzt')->send()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_any_campaign')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallCampaigns::route('/'),
            'create' => Pages\CreateCallCampaign::route('/create'),
            'view' => Pages\ViewCallCampaign::route('/{record}'),
            'edit' => Pages\EditCallCampaign::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes();
    }
}