<?php

namespace App\Filament\Admin\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommunicationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'communicationLogs';
    
    protected static ?string $title = 'Kommunikationsverlauf';
    
    protected static ?string $recordTitleAttribute = 'subject';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Typ')
                    ->options([
                        'email' => 'E-Mail',
                        'sms' => 'SMS',
                        'phone' => 'Anruf',
                        'whatsapp' => 'WhatsApp',
                        'letter' => 'Brief',
                    ])
                    ->required(),
                    
                Forms\Components\Select::make('direction')
                    ->label('Richtung')
                    ->options([
                        'outbound' => 'Ausgehend',
                        'inbound' => 'Eingehend',
                    ])
                    ->default('outbound')
                    ->required(),
                    
                Forms\Components\TextInput::make('subject')
                    ->label('Betreff')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('content')
                    ->label('Inhalt')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'sent' => 'Gesendet',
                        'delivered' => 'Zugestellt',
                        'read' => 'Gelesen',
                        'failed' => 'Fehlgeschlagen',
                        'bounced' => 'Zurückgewiesen',
                    ])
                    ->default('sent')
                    ->required(),
                    
                Forms\Components\KeyValue::make('metadata')
                    ->label('Zusätzliche Daten')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\IconColumn::make('type')
                    ->label('Typ')
                    ->icon(fn (string $state): string => match ($state) {
                        'email' => 'heroicon-o-envelope',
                        'sms' => 'heroicon-o-device-phone-mobile',
                        'phone' => 'heroicon-o-phone',
                        'whatsapp' => 'heroicon-o-chat-bubble-left-right',
                        'letter' => 'heroicon-o-document-text',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'info',
                        'sms' => 'success',
                        'phone' => 'warning',
                        'whatsapp' => 'success',
                        'letter' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\IconColumn::make('direction')
                    ->label('')
                    ->icon(fn (string $state): string => match ($state) {
                        'outbound' => 'heroicon-o-arrow-up-right',
                        'inbound' => 'heroicon-o-arrow-down-left',
                        default => 'heroicon-o-arrows-right-left',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'outbound' => 'primary',
                        'inbound' => 'success',
                        default => 'gray',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'outbound' => 'Ausgehend',
                        'inbound' => 'Eingehend',
                        default => 'Unbekannt',
                    }),
                    
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->subject),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => ['sent', 'delivered', 'read'],
                        'danger' => ['failed', 'bounced'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sent' => 'Gesendet',
                        'delivered' => 'Zugestellt',
                        'read' => 'Gelesen',
                        'failed' => 'Fehlgeschlagen',
                        'bounced' => 'Zurückgewiesen',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Von')
                    ->default('System')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'email' => 'E-Mail',
                        'sms' => 'SMS',
                        'phone' => 'Anruf',
                        'whatsapp' => 'WhatsApp',
                        'letter' => 'Brief',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent' => 'Gesendet',
                        'delivered' => 'Zugestellt',
                        'read' => 'Gelesen',
                        'failed' => 'Fehlgeschlagen',
                        'bounced' => 'Zurückgewiesen',
                    ])
                    ->multiple(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Kommunikation')
                    ->modalHeading('Neue Kommunikation erfassen')
                    ->modalWidth('lg')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['sent_at'] = now();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn ($record) => view('filament.resources.appointment.communication-view', [
                        'record' => $record
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Erneut senden')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['failed', 'bounced']))
                    ->action(function ($record) {
                        // Resend logic here
                        $record->update(['status' => 'sent']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Nachricht erneut gesendet')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Kommunikation vorhanden')
            ->emptyStateDescription('Erfassen Sie die erste Kommunikation für diesen Termin.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}