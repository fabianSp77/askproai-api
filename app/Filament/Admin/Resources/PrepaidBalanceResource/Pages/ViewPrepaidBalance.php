<?php

namespace App\Filament\Admin\Resources\PrepaidBalanceResource\Pages;

use App\Filament\Admin\Resources\PrepaidBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\BalanceTransaction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewPrepaidBalance extends ViewRecord
{
    protected static string $resource = PrepaidBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewPortal')
                ->label('Portal öffnen')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->action(function () {
                    // Generate admin access token
                    $token = bin2hex(random_bytes(32));
                    
                    // Store in cache for 15 minutes
                    cache()->put('admin_portal_access_' . $token, [
                        'admin_id' => auth()->id(),
                        'company_id' => $this->record->company_id,
                        'created_at' => now(),
                    ], now()->addMinutes(15));
                    
                    // Redirect to business portal
                    return redirect('/business/admin-access?token=' . $token);
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Guthaben-Informationen')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Firma'),
                                TextEntry::make('balance')
                                    ->label('Guthaben')
                                    ->money('EUR')
                                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                                TextEntry::make('reserved_balance')
                                    ->label('Reserviert')
                                    ->money('EUR'),
                                TextEntry::make('effective_balance')
                                    ->label('Verfügbar')
                                    ->getStateUsing(fn ($record) => $record->getEffectiveBalance())
                                    ->money('EUR')
                                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                                TextEntry::make('low_balance_threshold')
                                    ->label('Warnschwelle')
                                    ->money('EUR'),
                                TextEntry::make('last_warning_sent_at')
                                    ->label('Letzte Warnung')
                                    ->dateTime('d.m.Y H:i')
                                    ->default('Keine'),
                            ]),
                    ]),
                    
                Section::make('Letzte Transaktionen')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('recent_transactions')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $transactions = BalanceTransaction::where('company_id', $record->company_id)
                                    ->latest()
                                    ->limit(10)
                                    ->get();
                                
                                if ($transactions->isEmpty()) {
                                    return 'Keine Transaktionen vorhanden';
                                }
                                
                                $html = '<div class="space-y-2">';
                                foreach ($transactions as $transaction) {
                                    $type = $transaction->type === 'credit' ? '+' : '-';
                                    $color = $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600';
                                    $html .= sprintf(
                                        '<div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                            <div>
                                                <div class="text-sm font-medium">%s</div>
                                                <div class="text-xs text-gray-500">%s</div>
                                            </div>
                                            <div class="%s font-semibold">%s%.2f €</div>
                                        </div>',
                                        $transaction->description,
                                        $transaction->created_at->format('d.m.Y H:i'),
                                        $color,
                                        $type,
                                        $transaction->amount
                                    );
                                }
                                $html .= '</div>';
                                
                                return $html;
                            })
                            ->html(),
                    ]),
            ]);
    }
}