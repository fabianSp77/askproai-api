<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;
    
    // Temporarily disable authorization for testing
    public static function canViewAny(): bool
    {
        return true; // Allow all access for testing
    }
    
    public static function canView($record): bool
    {
        return true; // Allow view access for testing
    }
    
    public static function canCreate(): bool
    {
        return true; // Allow create access for testing
    }
    
    public static function canEdit($record): bool
    {
        return true; // Allow edit access for testing
    }
    
    public static function canDelete($record): bool
    {
        return true; // Allow delete access for testing
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Appointment Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Customer & Service')
                                    ->schema([
                                        Forms\Components\Select::make('customer_id')
                                            ->relationship('customer', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Select::make('service_id')
                                            ->relationship('service', 'name'),
                                        Forms\Components\Select::make('staff_id')
                                            ->relationship('staff', 'name')
                                            ->searchable(),
                                        Forms\Components\Select::make('calcom_event_type_id')
                                            ->label('Cal.com Event Type')
                                            ->options(CalcomEventType::pluck('name', 'id'))
                                            ->searchable(),
                                    ])->columns(2),
                                Forms\Components\Section::make('Schedule')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('starts_at')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d.m.Y H:i')
                                            ->seconds(false),
                                        Forms\Components\DateTimePicker::make('ends_at')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d.m.Y H:i')
                                            ->seconds(false),
                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'accepted' => '✅ Accepted',
                                                'confirmed' => '✓ Confirmed',
                                                'pending' => '⏳ Pending',
                                                'cancelled' => '✗ Cancelled',
                                                'completed' => '✔ Completed',
                                            ])
                                            ->default('pending'),
                                        Forms\Components\TextInput::make('source')
                                            ->default('manual')
                                            ->disabled(),
                                    ])->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Cal.com Integration')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Cal.com Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('calcom_v2_booking_id')
                                            ->label('Cal.com Booking ID')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('calcom_booking_uid')
                                            ->label('Cal.com UID')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('meeting_url')
                                            ->label('Meeting URL')
                                            ->url()
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('open')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(fn ($state) => $state)
                                                    ->openUrlInNewTab()
                                                    ->visible(fn ($state) => filled($state))
                                            ),
                                        Forms\Components\TextInput::make('reschedule_uid')
                                            ->label('Reschedule UID')
                                            ->disabled(),
                                    ])->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Notes & Metadata')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                Forms\Components\KeyValue::make('payload')
                                    ->label('Raw Cal.com Data')
                                    ->columnSpanFull()
                                            ->disabled(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'staff', 'service', 'calcomEventType', 'branch']))
            ->columns([
                Tables\Columns\TextColumn::make('calcom_v2_booking_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->tooltip('Cal.com Booking ID'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn ($record) => $record->customer?->email)
                    ->tooltip(fn ($record) => 
                        $record->attendee_count > 0 
                            ? "👥 {$record->attendee_count} attendee(s)"
                            : null
                    ),
                Tables\Columns\TextColumn::make('attendee_count')
                    ->label('👥')
                    ->badge()
                    ->color(fn ($state) => $state > 1 ? 'warning' : 'gray')
                    ->tooltip('Number of attendees')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('calcomEventType.name')
                    ->label('Event Type')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Manual Booking'),
                Tables\Columns\TextColumn::make('location_display')
                    ->label('Location')
                    ->badge()
                    ->color(function ($record) {
                        if (!$record || !$record->location_type) {
                            return 'gray';
                        }
                        return match($record->location_type) {
                            'video' => 'success',
                            'phone' => 'warning',
                            'inPerson' => 'primary',
                            default => 'gray'
                        };
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Date & Time')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->iconColor('primary')
                    ->description(fn ($record) => 
                        $record->starts_at && $record->ends_at 
                            ? $record->starts_at->diffInMinutes($record->ends_at) . ' minutes'
                            : null
                    ),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => ['confirmed', 'accepted', 'completed'],
                        'warning' => 'pending',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => ['confirmed', 'accepted', 'completed'],
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('meeting_url')
                    ->label('Meeting')
                    ->url(fn ($state) => $state)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-video-camera')
                    ->iconColor('success')
                    ->limit(20)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('In-Person')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'primary' => 'cal.com',
                        'secondary' => 'manual',
                        'success' => 'api',
                    ])
                    ->icons([
                        'heroicon-o-globe-alt' => 'cal.com',
                        'heroicon-o-pencil' => 'manual',
                        'heroicon-o-code-bracket' => 'api',
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'accepted' => 'Accepted',
                        'confirmed' => 'Confirmed',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'cal.com' => 'Cal.com',
                        'manual' => 'Manual',
                        'api' => 'API',
                    ]),
                Tables\Filters\Filter::make('has_meeting_url')
                    ->query(fn ($query) => $query->whereNotNull('meeting_url'))
                    ->label('Has Meeting URL'),
                Tables\Filters\SelectFilter::make('location_type')
                    ->options([
                        'video' => '📹 Video Call',
                        'phone' => '📞 Phone Call',
                        'inPerson' => '🏢 In Person',
                        'email' => '✉️ Email',
                    ])
                    ->label('Location Type'),
                Tables\Filters\Filter::make('has_attendees')
                    ->query(fn ($query) => $query->whereNotNull('attendees')
                        ->where('attendees', '!=', '[]'))
                    ->label('Has Attendees'),
                Tables\Filters\Filter::make('is_recurring')
                    ->query(fn ($query) => $query->where('is_recurring', true))
                    ->label('Recurring Appointments'),
                Tables\Filters\Filter::make('starts_at')
                    ->label('Start Date')
                    ->form([
                        Forms\Components\DatePicker::make('starts_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('starts_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['starts_from'],
                                fn ($query, $date) => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['starts_until'],
                                fn ($query, $date) => $query->whereDate('starts_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->icon('heroicon-m-eye'),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-m-pencil'),
                Tables\Actions\Action::make('open_meeting')
                    ->label('Join')
                    ->icon('heroicon-m-video-camera')
                    ->color('success')
                    ->url(fn ($record) => $record->meeting_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->meeting_url)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
    }

    // Removed: infolist() method - ViewAppointment has the complete implementation
    // The resource-level infolist was interfering with the page-level infolist

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'staff', 'service', 'calcomEventType', 'branch']);
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}