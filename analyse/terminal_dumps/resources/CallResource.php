<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Models\Call;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;
    protected static ?string $navigationGroup = 'Kommunikation';
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Anrufe';
    protected static bool $shouldRegisterNavigation = true;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('from_number')->label('Von')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('to_number')->label('Zu')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('call_status')->label('Status')->sortable(),
                Tables\Columns\TextColumn::make('duration_sec')->label('Dauer (Sekunden)')->sortable(),
                Tables\Columns\TextColumn::make('retell_call_id')->label('Retell-Call-ID')->toggleable()->searchable(),
                Tables\Columns\TextColumn::make('call_id')->label('Call-ID')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                Tables\Columns\TextColumn::make('external_id')->label('External-ID')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                Tables\Columns\TextColumn::make('conversation_id')->label('Conversation-ID')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                Tables\Columns\TextColumn::make('branch_id')->label('Filiale')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                Tables\Columns\TextColumn::make('tenant_id')->label('Mandant')->toggleable(isToggledHiddenByDefault: true)->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('call_status')
                    ->label('Status')
                    ->options([
                        'in_progress' => 'Laufend',
                        'completed'   => 'Beendet',
                        'analyzed'    => 'Analysiert',
                        'manual_test' => 'Manuell (Test)',
                        'unknown'     => 'Unbekannt',
                    ]),
                Tables\Filters\Filter::make('from_number')
                    ->label('Von-Nummer')
                    ->form([ Forms\Components\TextInput::make('value')->label('Von-Nummer'), ])
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->where('from_number', 'like', '%' . $data['value'] . '%');
                        }
                    }),
                Tables\Filters\Filter::make('to_number')
                    ->label('Zu-Nummer')
                    ->form([ Forms\Components\TextInput::make('value')->label('Zu-Nummer'), ])
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->where('to_number', 'like', '%' . $data['value'] . '%');
                        }
                    }),
                Tables\Filters\Filter::make('call_id')
                    ->label('Call-ID')
                    ->form([ Forms\Components\TextInput::make('value')->label('Call-ID'), ])
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->where('call_id', 'like', '%' . $data['value'] . '%');
                        }
                    }),
                Tables\Filters\Filter::make('retell_call_id')
                    ->label('Retell-Call-ID')
                    ->form([ Forms\Components\TextInput::make('value')->label('Retell-Call-ID'), ])
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->where('retell_call_id', 'like', '%' . $data['value'] . '%');
                        }
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Datum ab'),
                        Forms\Components\DatePicker::make('created_until')->label('Datum bis'),
                    ])
                    ->query(function ($query, $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['created_until'], fn($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Anzeigen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
            'create' => Pages\CreateCall::route('/create'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
            'view' => Pages\ViewCall::route('/{record}'),
        ];
    }
}
