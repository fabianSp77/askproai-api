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
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->iconColor('primary')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Anrufer')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->iconColor('primary')
                    ->copyable()
                    ->default('Anonym')
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
                    ->icon('heroicon-m-clock')
                    ->alignCenter()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('appointment_made')
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('call_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ended', 'completed' => 'Beendet',
                        'error' => 'Fehler',
                        'ongoing' => 'Laufend',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ended', 'completed' => 'success',
                        'error' => 'danger',
                        'ongoing' => 'warning',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                    
                Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
                    
                TernaryFilter::make('appointment_made')
                    ->label('Mit Termin')
                    ->placeholder('Alle Anrufe')
                    ->trueLabel('Mit Termin')
                    ->falseLabel('Ohne Termin'),
                    
                SelectFilter::make('call_status')
                    ->label('Status')
                    ->options([
                        'ended' => 'Beendet',
                        'error' => 'Fehler',
                        'ongoing' => 'Laufend',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->icon('heroicon-m-eye'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hauptinformationen
                Infolists\Components\Section::make('Anruf-Übersicht')
                    ->icon('heroicon-o-phone')
                    ->description('Wichtigste Informationen zu diesem Anruf')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Anrufzeit')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-m-calendar')
                                    ->weight(FontWeight::Medium),
                                    
                                Infolists\Components\TextEntry::make('duration_sec')
                                    ->label('Gesprächsdauer')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return 'Keine Dauer';
                                        $minutes = floor($state / 60);
                                        $seconds = $state % 60;
                                        return sprintf('%d:%02d Min', $minutes, $seconds);
                                    })
                                    ->icon('heroicon-m-clock')
                                    ->weight(FontWeight::Medium),
                                    
                                Infolists\Components\TextEntry::make('call_status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'ended', 'completed' => 'Erfolgreich beendet',
                                        'error' => 'Fehler',
                                        'ongoing' => 'Laufend',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'ended', 'completed' => 'success',
                                        'error' => 'danger',
                                        'ongoing' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),
                    
                // Kunde und Termin
                Infolists\Components\Section::make('Kunde & Terminvereinbarung')
                    ->icon('heroicon-o-user')
                    ->description(fn ($record) => 
                        $record->customer 
                            ? 'Kunde identifiziert und zugeordnet' 
                            : 'Anonymer Anruf'
                    )
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('customer.name')
                                        ->label('Kundenname')
                                        ->icon('heroicon-m-user')
                                        ->weight(FontWeight::Medium)
                                        ->default('Anonymer Anrufer')
                                        ->url(fn ($record) => $record->customer 
                                            ? CustomerResource::getUrl('view', [$record->customer]) 
                                            : null),
                                        
                                    Infolists\Components\TextEntry::make('from_number')
                                        ->label('Telefonnummer')
                                        ->icon('heroicon-m-phone')
                                        ->copyable()
                                        ->copyMessage('Nummer kopiert!')
                                        ->default('Nummer unterdrückt')
                                        ->formatStateUsing(fn ($state) => 
                                            $state ? preg_replace('/(\d{4})(\d{3})(\d+)/', '+49 $1 $2 $3', $state) : 'Anonym'
                                        ),
                                ])
                                ->columnSpan(1),
                                
                                Infolists\Components\Group::make([
                                    Infolists\Components\IconEntry::make('appointment_made')
                                        ->label('Termin vereinbart')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-check-circle')
                                        ->falseIcon('heroicon-o-x-circle')
                                        ->trueColor('success')
                                        ->falseColor('gray')
                                        ->size('lg'),
                                        
                                    Infolists\Components\TextEntry::make('appointment.scheduled_at')
                                        ->label('Terminzeit')
                                        ->dateTime('d.m.Y um H:i Uhr')
                                        ->icon('heroicon-m-calendar-days')
                                        ->visible(fn ($record) => $record->appointment_made)
                                        ->url(fn ($record) => $record->appointment 
                                            ? AppointmentResource::getUrl('view', [$record->appointment]) 
                                            : null),
                                        
                                    Infolists\Components\TextEntry::make('appointment.service.name')
                                        ->label('Gebuchter Service')
                                        ->icon('heroicon-m-briefcase')
                                        ->visible(fn ($record) => $record->appointment_made),
                                ])
                                ->columnSpan(1),
                            ]),
                    ]),
                    
                // Audio nur wenn vorhanden
                Infolists\Components\Section::make('Aufzeichnung')
                    ->icon('heroicon-o-microphone')
                    ->description('Gesprächsaufzeichnung')
                    ->visible(fn ($record) => 
                        $record->recording_url || 
                        $record->audio_url || 
                        ($record->webhook_data['recording_url'] ?? null)
                    )
                    ->schema([
                        Infolists\Components\TextEntry::make('recording_url')
                            ->label('')
                            ->formatStateUsing(function ($state, $record) {
                                $url = $record->recording_url ?? $record->audio_url ?? ($record->webhook_data['recording_url'] ?? null);
                                if (!$url) return null;
                                
                                return new HtmlString(sprintf(
                                    '<div class="space-y-2">
                                        <audio controls class="w-full" style="max-width: 600px;">
                                            <source src="%s" type="audio/mpeg">
                                            <source src="%s" type="audio/wav">
                                            Ihr Browser unterstützt keine Audio-Wiedergabe.
                                        </audio>
                                        <p class="text-sm text-gray-500">
                                            Hinweis: Die Aufzeichnung dient nur zu Qualitätssicherungszwecken
                                        </p>
                                    </div>',
                                    htmlspecialchars($url),
                                    htmlspecialchars($url)
                                ));
                            })
                            ->columnSpanFull(),
                    ]),
                    
                // Transkript nur wenn vorhanden und formatiert
                Infolists\Components\Section::make('Gesprächsverlauf')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Transkript des Gesprächs')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->transcript))
                    ->schema([
                        Infolists\Components\TextEntry::make('transcript')
                            ->label('')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return null;
                                
                                $html = '<div class="space-y-3 max-h-[500px] overflow-y-auto">';
                                
                                if (is_array($state)) {
                                    foreach ($state as $entry) {
                                        $speaker = $entry['role'] ?? 'unknown';
                                        $text = $entry['content'] ?? '';
                                        
                                        $speakerLabel = $speaker === 'agent' ? 'KI-Assistent' : 'Anrufer';
                                        $bgClass = $speaker === 'agent' 
                                            ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' 
                                            : 'bg-gray-50 dark:bg-gray-800/50 border-l-4 border-gray-400';
                                        
                                        $html .= sprintf(
                                            '<div class="p-4 rounded-lg %s">
                                                <div class="font-semibold text-sm mb-1">%s</div>
                                                <div class="text-gray-700 dark:text-gray-300">%s</div>
                                            </div>',
                                            $bgClass,
                                            htmlspecialchars($speakerLabel),
                                            htmlspecialchars($text)
                                        );
                                    }
                                } elseif (is_string($state)) {
                                    $html .= '<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <pre class="whitespace-pre-wrap text-sm">' . htmlspecialchars($state) . '</pre>
                                    </div>';
                                }
                                
                                $html .= '</div>';
                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
            'create' => Pages\CreateCall::route('/create'),
            'view' => Pages\ViewCall::route('/{record}'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
        ];
    }
    
    public static function getWidgets(): array
    {
        return [];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() > 0 ? 'primary' : 'gray';
    }
}