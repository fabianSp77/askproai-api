<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Filament\Admin\Resources\CallResource\Widgets;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Admin\Resources\AppointmentResource;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationLabel = 'Anrufe';
    protected static ?string $navigationGroup = 'Tagesgeschäft';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $recordTitleAttribute = 'call_id';
    
    public static function canViewAny(): bool
    {
        return true;
    }
    
    public static function canView($record): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->with(['customer', 'appointment', 'branch', 'company'])
            )
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Anrufstart')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->icon('heroicon-m-phone-arrow-down-left')
                    ->iconColor('primary')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Anrufer')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Nummer kopiert!')
                    ->copyMessageDuration(1500)
                    ->default('—')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->customer 
                        ? CustomerResource::getUrl('view', [$record->customer]) 
                        : null
                    )
                    ->default('—')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '—')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                    
                Tables\Columns\BadgeColumn::make('sentiment')
                    ->label('Stimmung')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'positive' => 'Positiv',
                        'negative' => 'Negativ',
                        'neutral' => 'Neutral',
                        default => '—'
                    })
                    ->color(fn ($state) => match($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        'neutral' => 'gray',
                        default => 'secondary'
                    })
                    ->icon(fn ($state) => match($state) {
                        'positive' => 'heroicon-m-face-smile',
                        'negative' => 'heroicon-m-face-frown',
                        'neutral' => 'heroicon-m-minus-circle',
                        default => 'heroicon-m-question-mark-circle'
                    })
                    ->toggleable(),
                    
                Tables\Columns\BadgeColumn::make('call_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'ended' => 'Beendet',
                        'completed' => 'Abgeschlossen',
                        'analyzed' => 'Analysiert',
                        'error' => 'Fehler',
                        default => ucfirst($state ?? 'unbekannt')
                    })
                    ->color(fn ($state) => match($state) {
                        'ended', 'completed', 'analyzed' => 'success',
                        'error' => 'danger',
                        default => 'gray'
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('call_status')
                    ->label('Status')
                    ->options([
                        'ended' => 'Beendet',
                        'completed' => 'Abgeschlossen',
                        'analyzed' => 'Analysiert',
                        'error' => 'Fehler',
                    ]),
                    
                SelectFilter::make('sentiment')
                    ->label('Stimmung')
                    ->options([
                        'positive' => 'Positiv',
                        'negative' => 'Negativ',
                        'neutral' => 'Neutral',
                    ]),
                    
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Von'),
                        DatePicker::make('created_until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
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
    
    public static function getWidgets(): array
    {
        return [];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Anruf-Details')
                    ->icon('heroicon-o-phone')
                    ->description('Grundlegende Informationen zu diesem Anruf')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Anruf ID')
                                    ->badge()
                                    ->color('gray'),
                                    
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Datum & Zeit')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-m-calendar'),
                                    
                                Infolists\Components\TextEntry::make('duration_sec')
                                    ->label('Dauer')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) . ' Min' : '—')
                                    ->icon('heroicon-m-clock'),
                                    
                                Infolists\Components\TextEntry::make('from_number')
                                    ->label('Anrufer')
                                    ->icon('heroicon-m-phone')
                                    ->copyable()
                                    ->copyMessage('Nummer kopiert!')
                                    ->default('Unbekannt'),
                                    
                                Infolists\Components\TextEntry::make('to_number')
                                    ->label('Angerufene Nummer')
                                    ->icon('heroicon-m-phone-arrow-down-left')
                                    ->copyable()
                                    ->default('—'),
                                    
                                Infolists\Components\TextEntry::make('call_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'ended', 'completed' => 'success',
                                        'error' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Kunde & Termin')
                    ->icon('heroicon-o-user')
                    ->description('Kundendaten und Terminvereinbarung')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Kundenname')
                                    ->icon('heroicon-m-user')
                                    ->url(fn ($record) => $record->customer 
                                        ? CustomerResource::getUrl('view', [$record->customer]) 
                                        : null)
                                    ->default('Kein Kunde zugeordnet'),
                                    
                                Infolists\Components\TextEntry::make('customer.phone')
                                    ->label('Telefonnummer')
                                    ->icon('heroicon-m-phone')
                                    ->copyable()
                                    ->default('—'),
                                    
                                Infolists\Components\TextEntry::make('appointment.scheduled_at')
                                    ->label('Terminzeit')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-m-calendar-days')
                                    ->visible(fn ($record) => $record->appointment !== null)
                                    ->url(fn ($record) => $record->appointment 
                                        ? AppointmentResource::getUrl('view', [$record->appointment]) 
                                        : null),
                                    
                                Infolists\Components\TextEntry::make('appointment.service.name')
                                    ->label('Service')
                                    ->icon('heroicon-m-briefcase')
                                    ->visible(fn ($record) => $record->appointment !== null),
                                    
                                Infolists\Components\IconEntry::make('appointment_made')
                                    ->label('Termin vereinbart')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                    
                                Infolists\Components\TextEntry::make('sentiment')
                                    ->label('Stimmung')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'positive' => 'success',
                                        'negative' => 'danger',
                                        'neutral' => 'gray',
                                        default => 'gray',
                                    })
                                    ->default('Nicht analysiert'),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Aufzeichnung & Transkript')
                    ->icon('heroicon-o-microphone')
                    ->description('Audio-Aufzeichnung und Gesprächstranskript')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('recording_url')
                            ->label('Audio-Aufzeichnung')
                            ->formatStateUsing(function ($state, $record) {
                                $url = $record->recording_url ?? $record->audio_url ?? ($record->webhook_data['recording_url'] ?? null);
                                if (!$url) return '<span class="text-gray-500">Keine Aufzeichnung verfügbar</span>';
                                
                                return sprintf(
                                    '<audio controls class="w-full max-w-md">
                                        <source src="%s" type="audio/mpeg">
                                        Ihr Browser unterstützt kein Audio-Element.
                                    </audio>',
                                    htmlspecialchars($url)
                                );
                            })
                            ->html()
                            ->columnSpanFull()
                            ->visible(fn ($record) => 
                                $record->recording_url || 
                                $record->audio_url || 
                                ($record->webhook_data['recording_url'] ?? null)
                            ),
                            
                        Infolists\Components\TextEntry::make('transcript')
                            ->label('Transkript')
                            ->html()
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '<span class="text-gray-500">Kein Transkript verfügbar</span>';
                                
                                $html = '<div class="space-y-2 max-h-96 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">';
                                
                                if (is_array($state)) {
                                    foreach ($state as $entry) {
                                        $speaker = $entry['role'] ?? 'unknown';
                                        $text = $entry['content'] ?? '';
                                        $bgClass = $speaker === 'agent' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-gray-100 dark:bg-gray-800';
                                        $html .= sprintf(
                                            '<div class="p-3 rounded %s"><strong>%s:</strong> %s</div>',
                                            $bgClass,
                                            ucfirst($speaker),
                                            htmlspecialchars($text)
                                        );
                                    }
                                } else {
                                    $html .= '<pre class="whitespace-pre-wrap">' . htmlspecialchars($state) . '</pre>';
                                }
                                
                                $html .= '</div>';
                                return $html;
                            })
                            ->columnSpanFull(),
                    ]),
                    
                Infolists\Components\Section::make('Technische Details')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->description('Technische Informationen und Kosten')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Retell Call ID')
                                    ->copyable()
                                    ->formatStateUsing(fn ($state) => $state ? $state : '—')
                                    ->extraAttributes(['class' => 'font-mono text-xs']),
                                    
                                Infolists\Components\TextEntry::make('agent_id')
                                    ->label('Agent ID')
                                    ->copyable()
                                    ->formatStateUsing(fn ($state) => $state ? $state : '—')
                                    ->extraAttributes(['class' => 'font-mono text-xs']),
                                    
                                Infolists\Components\TextEntry::make('call_type')
                                    ->label('Anruftyp')
                                    ->badge()
                                    ->default('—'),
                                    
                                Infolists\Components\TextEntry::make('disconnection_reason')
                                    ->label('Beendigungsgrund')
                                    ->default('—'),
                                    
                                Infolists\Components\TextEntry::make('call_cost')
                                    ->label('Kosten')
                                    ->money('EUR')
                                    ->default(0),
                                    
                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-m-building-office')
                                    ->default('—'),
                            ]),
                    ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() > 0 ? 'primary' : 'gray';
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'appointment', 'company', 'branch']);
    }
}