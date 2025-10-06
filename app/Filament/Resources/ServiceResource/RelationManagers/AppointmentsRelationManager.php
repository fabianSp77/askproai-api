<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';
    protected static ?string $title = 'Buchungen';
    protected static ?string $icon = 'heroicon-o-calendar-days';
    protected static ?string $recordTitleAttribute = 'starts_at';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Termin Details')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('customer_id')
                                ->label('Kunde')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Name')
                                        ->required(),
                                    Forms\Components\TextInput::make('email')
                                        ->label('E-Mail')
                                        ->email()
                                        ->required(),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('Telefon'),
                                ]),

                            Forms\Components\Select::make('staff_id')
                                ->label('Mitarbeiter')
                                ->relationship('staff', 'name')
                                ->searchable()
                                ->preload(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Termin')
                                ->required()
                                ->seconds(false)
                                ->default(now()->addDay()->setHour(10)->setMinute(0)),

                            Forms\Components\TextInput::make('duration_minutes')
                                ->label('Dauer')
                                ->numeric()
                                ->suffix('min')
                                ->default(fn () => $this->ownerRecord->duration_minutes ?? 30)
                                ->required(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('price')
                                ->label('Preis')
                                ->prefix('€')
                                ->numeric()
                                ->step(0.01)
                                ->default(fn () => $this->ownerRecord->price ?? 0),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => '⏳ Ausstehend',
                                    'confirmed' => '✅ Bestätigt',
                                    'completed' => '✔️ Abgeschlossen',
                                    'cancelled' => '❌ Storniert',
                                    'no_show' => '🚫 Nicht erschienen',
                                ])
                                ->default('pending')
                                ->required(),
                        ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('starts_at')
            ->columns([
                // Date & Time
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->description(fn ($record) =>
                        'Dauer: ' . $record->duration_minutes . ' min' .
                        ($record->ends_at ? ' bis ' . Carbon::parse($record->ends_at)->format('H:i') : '')
                    ),

                // Customer
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->customer?->email),

                // Staff
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Nicht zugewiesen'),

                // Status
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                        'warning' => 'no_show',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => '⏳ Ausstehend',
                        'confirmed' => '✅ Bestätigt',
                        'completed' => '✔️ Abgeschlossen',
                        'cancelled' => '❌ Storniert',
                        'no_show' => '🚫 Nicht erschienen',
                        default => $state,
                    }),

                // Branch
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                // Price
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                // Source
                Tables\Columns\BadgeColumn::make('source')
                    ->label('Quelle')
                    ->colors([
                        'success' => 'admin',
                        'info' => 'calcom',
                        'warning' => 'phone',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'admin' => '💻 Admin',
                        'calcom' => '🌐 Cal.com',
                        'phone' => '📞 Telefon',
                        'other' => '📋 Sonstige',
                        default => $state ?? '❓ Unbekannt',
                    })
                    ->toggleable(),

                // Reminder
                Tables\Columns\IconColumn::make('reminder_sent')
                    ->label('Erinnerung')
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'pending' => '⏳ Ausstehend',
                        'confirmed' => '✅ Bestätigt',
                        'completed' => '✔️ Abgeschlossen',
                        'cancelled' => '❌ Storniert',
                        'no_show' => '🚫 Nicht erschienen',
                    ]),

                Filter::make('upcoming')
                    ->label('Zukünftige')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('starts_at', '>=', now())
                    )
                    ->default(),

                Filter::make('past')
                    ->label('Vergangene')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('starts_at', '<', now())
                    ),

                Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereDate('starts_at', today())
                    ),

                Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereBetween('starts_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                    ),

                SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable(),

                SelectFilter::make('source')
                    ->label('Quelle')
                    ->options([
                        'admin' => '💻 Admin',
                        'calcom' => '🌐 Cal.com',
                        'phone' => '📞 Telefon',
                        'other' => '📋 Sonstige',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neuer Termin')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-fill service_id from parent record
                        $data['service_id'] = $this->ownerRecord->id;

                        // Calculate ends_at from starts_at and duration
                        if (isset($data['starts_at']) && isset($data['duration_minutes'])) {
                            $data['ends_at'] = Carbon::parse($data['starts_at'])
                                ->addMinutes($data['duration_minutes'])
                                ->toDateTimeString();
                        }

                        // Set default source
                        $data['source'] = $data['source'] ?? 'admin';

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('confirm')
                        ->label('Bestätigen')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn ($record) => $record->update(['status' => 'confirmed']))
                        ->visible(fn ($record) => $record->status === 'pending'),

                    Tables\Actions\Action::make('complete')
                        ->label('Abschließen')
                        ->icon('heroicon-m-check-circle')
                        ->color('info')
                        ->action(fn ($record) => $record->update(['status' => 'completed']))
                        ->visible(fn ($record) => $record->status === 'confirmed'),

                    Tables\Actions\Action::make('cancel')
                        ->label('Stornieren')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'cancelled']))
                        ->visible(fn ($record) => !in_array($record->status, ['cancelled', 'completed'])),

                    Tables\Actions\Action::make('reschedule')
                        ->label('Umbuchen')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Neuer Termin')
                                ->required()
                                ->seconds(false)
                                ->default(fn ($record) => $record->starts_at),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'starts_at' => $data['starts_at'],
                                'ends_at' => Carbon::parse($data['starts_at'])
                                    ->addMinutes($record->duration_minutes)
                                    ->toDateTimeString(),
                            ]);

                            Notification::make()
                                ->title('Termin umgebucht')
                                ->body('Der Termin wurde erfolgreich verschoben.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkConfirm')
                        ->label('Bestätigen')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'confirmed']))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('sendReminders')
                        ->label('Erinnerungen senden')
                        ->icon('heroicon-m-bell')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['reminder_sent' => true]);
                                // TODO: Actually send reminder
                            });

                            Notification::make()
                                ->title('Erinnerungen versendet')
                                ->body(count($records) . ' Erinnerungen wurden versendet.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkCancel')
                        ->label('Stornieren')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['status' => 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Termine stornieren')
                        ->modalDescription('Sind Sie sicher, dass Sie diese Termine stornieren möchten?'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc')
            ->poll('60s');
    }
}