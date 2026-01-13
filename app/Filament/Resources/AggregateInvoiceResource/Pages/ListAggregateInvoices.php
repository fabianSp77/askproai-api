<?php

namespace App\Filament\Resources\AggregateInvoiceResource\Pages;

use App\Filament\Resources\AggregateInvoiceResource;
use App\Models\AggregateInvoice;
use App\Models\Company;
use App\Services\Billing\MonthlyBillingAggregator;
use App\Services\Billing\StripeInvoicingService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ListAggregateInvoices extends ListRecords
{
    protected static string $resource = AggregateInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Rechnungen generieren')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('partner_id')
                        ->label('Partner')
                        ->options(Company::where('is_partner', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Leer lassen für alle Partner'),

                    Forms\Components\Select::make('month')
                        ->label('Abrechnungsmonat')
                        ->options(function () {
                            $options = [];
                            for ($i = 0; $i < 12; $i++) {
                                $date = now()->subMonths($i)->startOfMonth();
                                $options[$date->format('Y-m')] = $date->format('F Y');
                            }
                            return $options;
                        })
                        ->default(now()->subMonth()->format('Y-m'))
                        ->required(),

                    Forms\Components\Toggle::make('send_immediately')
                        ->label('Sofort versenden')
                        ->helperText('Rechnungen direkt an Partner senden')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $this->generateInvoices($data);
                }),

            Actions\CreateAction::make()
                ->label('Manuelle Rechnung'),
        ];
    }

    protected function generateInvoices(array $data): void
    {
        $periodStart = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $partnerId = $data['partner_id'] ?? null;
        $sendImmediately = $data['send_immediately'] ?? false;

        $partners = $partnerId
            ? Company::where('id', $partnerId)->where('is_partner', true)->get()
            : Company::where('is_partner', true)->where('is_active', true)->get();

        if ($partners->isEmpty()) {
            Notification::make()
                ->title('Keine Partner gefunden')
                ->warning()
                ->send();
            return;
        }

        $aggregator = app(MonthlyBillingAggregator::class);
        $stripeService = app(StripeInvoicingService::class);

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($partners as $partner) {
            try {
                // Check if invoice already exists (non-voided)
                $existing = AggregateInvoice::where('partner_company_id', $partner->id)
                    ->forPeriod($periodStart, $periodEnd)
                    ->whereNotIn('status', [AggregateInvoice::STATUS_VOID])
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Hard-delete any voided invoices for this period (to satisfy unique constraints)
                // Using forceDelete() because soft-delete still keeps invoice_number in DB
                AggregateInvoice::where('partner_company_id', $partner->id)
                    ->forPeriod($periodStart, $periodEnd)
                    ->where('status', AggregateInvoice::STATUS_VOID)
                    ->forceDelete();

                // Check if there are any charges
                $summary = $aggregator->getChargesSummary($partner, $periodStart, $periodEnd);
                if ($summary['total_cents'] === 0) {
                    $skipped++;
                    continue;
                }

                DB::beginTransaction();

                // Create invoice
                $invoice = $stripeService->createMonthlyInvoice($partner, $periodStart, $periodEnd);
                $aggregator->populateInvoice($invoice, $partner, $periodStart, $periodEnd);

                if ($sendImmediately) {
                    $stripeService->finalizeAndSend($invoice);
                }

                DB::commit();
                $created++;

            } catch (\Exception $e) {
                DB::rollBack();
                $errors++;
                \Log::error("Failed to generate invoice for partner {$partner->id}: {$e->getMessage()}");
            }
        }

        $message = "{$created} Rechnung(en) erstellt";
        if ($skipped > 0) {
            $message .= ", {$skipped} übersprungen";
        }
        if ($errors > 0) {
            $message .= ", {$errors} Fehler";
        }

        Notification::make()
            ->title($message)
            ->color($errors > 0 ? 'warning' : 'success')
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AggregateInvoiceResource\Widgets\InvoiceStatsOverview::class,
        ];
    }
}
