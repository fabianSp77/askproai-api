<?php

namespace App\Filament\Actions;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Services\StripeCheckoutService;
use Filament\Forms;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditTopupAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('credit_topup');
        $this->label('Guthaben aufladen');
        $this->icon('heroicon-o-currency-euro');
        $this->color('success');
        $this->modalHeading('Guthaben aufladen');
        $this->modalDescription('Laden Sie das Guthaben für dieses Unternehmen auf');
        $this->modalWidth('md');

        $this->form($this->getFormSchema());

        $this->action(function (array $data, Model $record) {
            $this->processTopup($data, $record);
        });

        $this->visible(fn () => auth()->user()->can('manageBilling', Company::class));
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Aktuelles Guthaben')
                ->schema([
                    Forms\Components\Placeholder::make('current_balance')
                        ->label('Aktuelles Guthaben')
                        ->content(fn (?Model $record) => '€ ' . number_format($record?->credit_balance ?? 0, 2, ',', '.')),

                    Forms\Components\Placeholder::make('low_credit_threshold')
                        ->label('Warnschwelle')
                        ->content(fn (?Model $record) => '€ ' . number_format($record?->low_credit_threshold ?? 10, 2, ',', '.')),
                ])
                ->columns(2),

            Forms\Components\Select::make('amount')
                ->label('Betrag')
                ->options([
                    '10' => '€ 10,00',
                    '25' => '€ 25,00',
                    '50' => '€ 50,00',
                    '100' => '€ 100,00',
                    '250' => '€ 250,00',
                    '500' => '€ 500,00',
                    'custom' => 'Benutzerdefiniert',
                ])
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state !== 'custom') {
                        $set('custom_amount', null);
                    }
                }),

            Forms\Components\TextInput::make('custom_amount')
                ->label('Benutzerdefinierter Betrag')
                ->numeric()
                ->prefix('€')
                ->minValue(1)
                ->maxValue(10000)
                ->step(0.01)
                ->required(fn (Forms\Get $get) => $get('amount') === 'custom')
                ->visible(fn (Forms\Get $get) => $get('amount') === 'custom')
                ->reactive(),

            Forms\Components\Select::make('payment_method')
                ->label('Zahlungsmethode')
                ->options([
                    'stripe' => 'Kreditkarte (Stripe)',
                    'bank_transfer' => 'Banküberweisung',
                    'manual' => 'Manuell (Admin)',
                    'paypal' => 'PayPal',
                ])
                ->required()
                ->default('stripe')
                ->reactive()
                ->helperText(fn ($state) => $this->getPaymentMethodHelperText($state)),

            Forms\Components\Textarea::make('note')
                ->label('Notiz')
                ->rows(2)
                ->maxLength(500)
                ->placeholder('Optionale Notiz für diese Transaktion'),

            Forms\Components\Toggle::make('send_invoice')
                ->label('Rechnung erstellen')
                ->default(true)
                ->helperText('Automatisch eine Rechnung für diese Transaktion erstellen'),

            Forms\Components\Toggle::make('send_notification')
                ->label('Benachrichtigung senden')
                ->default(true)
                ->helperText('E-Mail-Benachrichtigung an den Kunden senden'),

            Forms\Components\Section::make('Automatische Aufladung')
                ->schema([
                    Forms\Components\Toggle::make('enable_auto_topup')
                        ->label('Auto-Aufladung aktivieren')
                        ->reactive()
                        ->afterStateUpdated(fn ($state, ?Model $record) =>
                            $this->updateAutoTopup($state, $record)
                        ),

                    Forms\Components\TextInput::make('auto_topup_amount')
                        ->label('Auto-Aufladungsbetrag')
                        ->numeric()
                        ->prefix('€')
                        ->default(50)
                        ->visible(fn (Forms\Get $get) => $get('enable_auto_topup')),

                    Forms\Components\TextInput::make('auto_topup_threshold')
                        ->label('Auslöseschwelle')
                        ->numeric()
                        ->prefix('€')
                        ->default(10)
                        ->helperText('Aufladung wird ausgelöst, wenn Guthaben unter diesen Wert fällt')
                        ->visible(fn (Forms\Get $get) => $get('enable_auto_topup')),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    protected function processTopup(array $data, Model $company): void
    {
        try {
            DB::beginTransaction();

            // Calculate final amount
            $amount = $data['amount'] === 'custom'
                ? (float) $data['custom_amount']
                : (float) $data['amount'];

            // Create transaction record
            $transaction = Transaction::create([
                'company_id' => $company->id,
                'type' => 'topup',
                'amount' => $amount,
                'status' => $data['payment_method'] === 'manual' ? 'completed' : 'pending',
                'payment_method' => $data['payment_method'],
                'description' => 'Guthaben Aufladung',
                'notes' => $data['note'] ?? null,
                'processed_at' => $data['payment_method'] === 'manual' ? now() : null,
                'processed_by' => $data['payment_method'] === 'manual' ? auth()->id() : null,
            ]);

            // Process payment based on method
            switch ($data['payment_method']) {
                case 'stripe':
                    $this->processStripePayment($transaction, $company, $amount);
                    break;

                case 'manual':
                    $this->processManualPayment($transaction, $company, $amount);
                    break;

                case 'bank_transfer':
                    $this->processBankTransfer($transaction, $company, $amount);
                    break;

                case 'paypal':
                    $this->processPayPalPayment($transaction, $company, $amount);
                    break;
            }

            // Create invoice if requested
            if ($data['send_invoice'] ?? false) {
                $this->createInvoice($transaction, $company);
            }

            // Send notification if requested
            if ($data['send_notification'] ?? false) {
                $this->sendNotification($company, $amount, $data['payment_method']);
            }

            // Update auto-topup settings if changed
            if (isset($data['enable_auto_topup'])) {
                $this->updateCompanyAutoTopup($company, $data);
            }

            DB::commit();

            Notification::make()
                ->title('Guthaben aufgeladen')
                ->body("€ {$amount} wurden erfolgreich aufgeladen. Neues Guthaben: € " .
                    number_format($company->fresh()->credit_balance, 2, ',', '.'))
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view_transaction')
                        ->label('Transaktion anzeigen')
                        ->url(route('filament.admin.resources.transactions.view', $transaction))
                ])
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Credit topup failed', [
                'company_id' => $company->id,
                'amount' => $amount ?? 0,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Fehler bei der Aufladung')
                ->body('Die Aufladung konnte nicht durchgeführt werden: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function processStripePayment(Transaction $transaction, Company $company, float $amount): void
    {
        // Create Stripe checkout session
        $stripe = app(StripeCheckoutService::class);

        $session = $stripe->createCheckoutSession([
            'customer_email' => $company->billing_contact_email ?? $company->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Guthaben Aufladung',
                        'description' => "Aufladung für {$company->name}",
                    ],
                    'unit_amount' => $amount * 100, // Stripe expects cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('filament.admin.resources.companies.view', [
                'record' => $company->id,
                'transaction' => $transaction->id,
                'status' => 'success'
            ]),
            'cancel_url' => route('filament.admin.resources.companies.view', [
                'record' => $company->id,
                'status' => 'cancelled'
            ]),
            'metadata' => [
                'transaction_id' => $transaction->id,
                'company_id' => $company->id,
            ],
        ]);

        // Update transaction with Stripe session ID
        $transaction->update([
            'stripe_session_id' => $session->id,
            'stripe_payment_url' => $session->url,
        ]);

        // Redirect to Stripe checkout
        redirect($session->url);
    }

    protected function processManualPayment(Transaction $transaction, Company $company, float $amount): void
    {
        // Immediately credit the account for manual payments
        $company->increment('credit_balance', $amount);

        // Update transaction
        $transaction->update([
            'status' => 'completed',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        // Log the manual credit
        activity()
            ->performedOn($company)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $amount,
                'previous_balance' => $company->credit_balance - $amount,
                'new_balance' => $company->credit_balance,
            ])
            ->log('Manual credit topup');
    }

    protected function processBankTransfer(Transaction $transaction, Company $company, float $amount): void
    {
        // Generate bank transfer reference
        $reference = 'TOP-' . $company->id . '-' . $transaction->id;

        $transaction->update([
            'reference' => $reference,
            'status' => 'awaiting_payment',
        ]);

        // The actual crediting would happen when bank transfer is confirmed
        // This could be done via webhook or manual confirmation
    }

    protected function processPayPalPayment(Transaction $transaction, Company $company, float $amount): void
    {
        // PayPal integration would go here
        // For now, just mark as pending
        $transaction->update([
            'status' => 'pending',
        ]);
    }

    protected function createInvoice(Transaction $transaction, Company $company): void
    {
        Invoice::create([
            'company_id' => $company->id,
            'transaction_id' => $transaction->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => $transaction->amount,
            'tax_rate' => 19.00, // German VAT
            'tax_amount' => $transaction->amount * 0.19,
            'total_amount' => $transaction->amount * 1.19,
            'status' => $transaction->status === 'completed' ? 'paid' : 'pending',
            'items' => json_encode([[
                'description' => 'Guthaben Aufladung',
                'quantity' => 1,
                'price' => $transaction->amount,
                'total' => $transaction->amount,
            ]]),
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return sprintf('INV-%d-%04d', $year, $number);
    }

    protected function sendNotification(Company $company, float $amount, string $paymentMethod): void
    {
        // Send email notification
        // This would integrate with your notification service
        Log::info('Credit topup notification would be sent', [
            'company_id' => $company->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ]);
    }

    protected function updateCompanyAutoTopup(Company $company, array $data): void
    {
        $company->update([
            'auto_topup_enabled' => $data['enable_auto_topup'] ?? false,
            'auto_topup_amount' => $data['auto_topup_amount'] ?? 50,
            'auto_topup_threshold' => $data['auto_topup_threshold'] ?? 10,
        ]);
    }

    protected function getPaymentMethodHelperText(string $method): string
    {
        return match($method) {
            'stripe' => 'Sichere Zahlung per Kreditkarte',
            'bank_transfer' => 'Überweisung auf unser Geschäftskonto',
            'manual' => 'Manuelle Gutschrift durch Administrator',
            'paypal' => 'Schnelle Zahlung mit PayPal',
            default => '',
        };
    }

    protected function updateAutoTopup(bool $state, ?Model $record): void
    {
        if ($record) {
            $record->update(['auto_topup_enabled' => $state]);
        }
    }
}