<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class RecentCallsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Aktuelle Anrufe';

    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $companyId = auth()->user()->company_id;

        try {
            return $table
                ->query(
                    Call::query()
                        ->select('id', 'created_at', 'customer_id', 'company_id', 'staff_id', 'duration_sec', 'from_number', 'to_number', 'recording_url')
                        ->with(['customer:id,name,customer_number', 'staff:id,name'])
                        ->where('company_id', $companyId)
                        ->where('created_at', '>=', now()->subHours(24))  // Nur letzte 24 Stunden
                        ->orderByRaw("
                            CASE
                                WHEN duration_sec = 0 THEN 1
                                WHEN duration_sec < 10 THEN 2
                                WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 3
                                ELSE 4
                            END,
                            created_at DESC
                        ")
                        ->limit(5)
                )
                ->columns([
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Datum')
                        ->formatStateUsing(function (Call $record) {
                            $time = Carbon::parse($record->created_at);
                            if ($time->isToday()) {
                                return 'Heute ' . $time->format('H:i');
                            } else {
                                return $time->format('d.m. H:i');
                            }
                        })
                        ->description(fn (Call $record) => $record->created_at->format('d.m.Y'))
                        ->icon('heroicon-m-clock')
                        ->color('gray')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('duration_sec')
                        ->label('Dauer')
                        ->formatStateUsing(function ($state) {
                            if (!$state) return '-';
                            if ($state < 60) return $state . ' Sek';
                            $minutes = floor($state / 60);
                            $seconds = $state % 60;
                            return sprintf('%d:%02d Min', $minutes, $seconds);
                        })
                        ->icon('heroicon-m-clock')
                        ->color(fn ($state) =>
                            !$state ? 'gray' :
                            ($state < 60 ? 'warning' :
                            ($state < 300 ? 'success' : 'info'))
                        )
                        ->alignment('center')
                        ->badge(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Status')
                        ->formatStateUsing(function (Call $record) {
                            if (!$record->duration_sec || $record->duration_sec == 0) {
                                return 'Verpasst';
                            } elseif ($record->duration_sec < 10) {
                                return 'Kurz';
                            } elseif ($record->recording_url) {
                                return 'Aufgezeichnet';
                            } else {
                                return 'Abgeschlossen';
                            }
                        })
                        ->badge()
                        ->color(function (Call $record) {
                            if (!$record->duration_sec || $record->duration_sec == 0) {
                                return 'danger';
                            } elseif ($record->duration_sec < 10) {
                                return 'warning';
                            } elseif ($record->recording_url) {
                                return 'info';
                            } else {
                                return 'success';
                            }
                        })
                        ->icon(function (Call $record) {
                            if (!$record->duration_sec || $record->duration_sec == 0) {
                                return 'heroicon-o-phone-x-mark';
                            } elseif ($record->recording_url) {
                                return 'heroicon-o-microphone';
                            } else {
                                return 'heroicon-o-check-circle';
                            }
                        }),

                    Tables\Columns\TextColumn::make('type')
                        ->label('Typ')
                        ->formatStateUsing(function (Call $record) {
                            // Determine if incoming or outgoing based on phone numbers
                            // This is a placeholder - adjust logic based on your system
                            if ($record->from_number && $record->to_number) {
                                return 'Eingehend';
                            }
                            return 'Ausgehend';
                        })
                        ->badge()
                        ->color(fn () => 'primary')
                        ->icon('heroicon-o-phone'),
                ])
                ->actions([
                    Tables\Actions\Action::make('view')
                        ->label('Details')
                        ->icon('heroicon-m-eye')
                        ->color('gray')
                        ->url(fn (Call $record): string =>
                            route('filament.customer.resources.calls.view', ['record' => $record])
                        ),

                    Tables\Actions\Action::make('transcript')
                        ->label('Transkript')
                        ->icon('heroicon-m-document-text')
                        ->color('info')
                        ->visible(fn (Call $record) => $record->recording_url)
                        ->url(fn (Call $record): string =>
                            route('filament.customer.resources.calls.view', ['record' => $record]) . '#transcript'
                        ),
                ])
                ->emptyState(
                    view('filament.widgets.empty-state', [
                        'icon' => 'heroicon-o-phone',
                        'heading' => 'Keine aktuellen Anrufe',
                        'description' => 'In den letzten 24 Stunden wurden keine Anrufe registriert.',
                    ])
                )
                ->bulkActions([])
                ->paginated(false)
                ->striped()
                ->poll('300s');
        } catch (\Exception $e) {
            \Log::error('RecentCallsWidget Error: ' . $e->getMessage());
            return $table
                ->query(Call::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }

    protected function getTableHeading(): string|HtmlString|null
    {
        $companyId = auth()->user()->company_id;

        $count = Call::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        $avgDuration = Call::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->where('duration_sec', '>', 0)
            ->avg('duration_sec');

        $avgDurationFormatted = $avgDuration ? floor($avgDuration / 60) . ':' . str_pad($avgDuration % 60, 2, '0', STR_PAD_LEFT) . ' Min' : 'N/A';

        return new HtmlString("
            <div class='flex items-center justify-between'>
                <span class='text-lg font-semibold'>Aktuelle Anrufe</span>
                <div class='flex gap-4 text-sm text-gray-500'>
                    <span>Heute: {$count} Anrufe</span>
                    <span>Ø Dauer: {$avgDurationFormatted}</span>
                </div>
            </div>
        ");
    }
}
