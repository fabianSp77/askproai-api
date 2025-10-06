<?php

namespace App\Filament\Widgets;

use App\Models\Call;
use App\Filament\Resources\CallResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class RecentCalls extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Aktuelle Anrufe';

    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        try {
        return $table
            ->query(
                Call::query()
                    ->select('id', 'created_at', 'customer_id', 'company_id', 'staff_id', 'duration_sec', 'from_number', 'to_number', 'recording_url')
                    ->with(['customer:id,name,customer_number', 'company:id,name', 'staff:id,name'])
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
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->description(fn (Call $record) => $record->created_at->format('d.m.Y'))
                    ->icon('heroicon-m-clock')
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (Call $record) => $record->customer?->name)
                    ->description(fn (Call $record) => $record->customer?->customer_number)
                    ->url(fn (Call $record) => $record->customer_id
                        ? route('filament.admin.resources.customers.view', $record->customer_id)
                        : null)
                    ->color('primary')
                    ->weight('medium'),

                // Status field doesn't exist in database - commented out
                // Tables\Columns\TextColumn::make('status')
                //     ->label('Status')
                //     ->badge(),

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
                    ->alignment('center'),

                // call_type field doesn't exist in database - using from/to numbers instead
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Von')
                    ->icon('heroicon-m-phone-arrow-up-right')
                    ->searchable()
                    ->limit(20),

                // sentiment field doesn't exist - showing to_number instead
                Tables\Columns\TextColumn::make('to_number')
                    ->label('An')
                    ->icon('heroicon-m-phone-arrow-down-left')
                    ->searchable()
                    ->limit(20),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Call $record): string => CallResource::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('play')
                    ->label('Anhören')
                    ->icon('heroicon-m-play')
                    ->color('info')
                    ->visible(fn (Call $record) => $record->recording_url)
                    ->url(fn (Call $record) => $record->recording_url)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->paginated(false)
            ->striped()
            ->poll('300s');
        } catch (\Exception $e) {
            \Log::error('RecentCalls Widget Error: ' . $e->getMessage());
            return $table
                ->query(Call::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }

    protected function getTableHeading(): string|HtmlString|null
    {
        $count = Call::whereDate('created_at', today())->count();
        return new HtmlString("
            <div class='flex items-center justify-between'>
                <span class='text-lg font-semibold'>Aktuelle Anrufe</span>
                <span class='text-sm text-gray-500'>Heute: {$count} Anrufe</span>
            </div>
        ");
    }
}
