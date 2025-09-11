<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Export logic here
                    return response()->streamDownload(function () {
                        $transactions = $this->getFilteredTableQuery()->get();
                        
                        echo "ID,Tenant,Typ,Betrag,Saldo,Beschreibung,Datum\n";
                        
                        foreach ($transactions as $transaction) {
                            echo sprintf(
                                "%d,\"%s\",\"%s\",%s,%s,\"%s\",\"%s\"\n",
                                $transaction->id,
                                $transaction->tenant->name ?? '',
                                $transaction->type,
                                number_format($transaction->amount_cents / 100, 2),
                                number_format($transaction->balance_after_cents / 100, 2),
                                str_replace('"', '""', $transaction->description),
                                $transaction->created_at->format('Y-m-d H:i:s')
                            );
                        }
                    }, 'transactions_' . now()->format('Y-m-d_His') . '.csv');
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            TransactionResource\Widgets\TransactionStats::class,
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-o-rectangle-stack'),
            
            'today' => Tab::make('Heute')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => $this->getModel()::whereDate('created_at', today())->count())
                ->badgeColor('info'),
            
            'credits' => Tab::make('Gutschriften')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('amount_cents', '>', 0))
                ->badge(fn () => $this->getModel()::where('amount_cents', '>', 0)->count())
                ->badgeColor('success'),
            
            'debits' => Tab::make('Belastungen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('amount_cents', '<', 0))
                ->badge(fn () => $this->getModel()::where('amount_cents', '<', 0)->count())
                ->badgeColor('danger'),
            
            'low_balance' => Tab::make('Niedriger Saldo')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('balance_after_cents', '<', 1000))
                ->badge(fn () => $this->getModel()::where('balance_after_cents', '<', 1000)->count())
                ->badgeColor('warning'),
        ];
    }
}