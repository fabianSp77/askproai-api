<?php

namespace App\Filament\Admin\Pages;

use App\Models\Company;
use App\Services\StripeTopupService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class StripePaymentLinks extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 45;
    protected static string $view = 'filament.admin.pages.stripe-payment-links';
    
    public static function getNavigationLabel(): string
    {
        return 'Payment Links';
    }
    
    public function getTitle(): string
    {
        return 'Stripe Payment Links';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(Company::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('metadata.stripe_payment_link_url')
                    ->label('Payment Link')
                    ->getStateUsing(function ($record) {
                        $metadata = $record->metadata ?? [];
                        return $metadata['stripe_payment_link_url'] ?? null;
                    })
                    ->copyable()
                    ->limit(50)
                    ->tooltip(function ($state) {
                        return $state ?: 'Kein Payment Link vorhanden';
                    }),
                    
                Tables\Columns\TextColumn::make('metadata.stripe_payment_link_created_at')
                    ->label('Erstellt')
                    ->getStateUsing(function ($record) {
                        $metadata = $record->metadata ?? [];
                        $createdAt = $metadata['stripe_payment_link_created_at'] ?? null;
                        return $createdAt ? \Carbon\Carbon::parse($createdAt)->format('d.m.Y H:i') : '-';
                    }),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $metadata = $record->metadata ?? [];
                        return isset($metadata['stripe_payment_link_url']) ? 'Aktiv' : 'Inaktiv';
                    })
                    ->colors([
                        'success' => 'Aktiv',
                        'secondary' => 'Inaktiv',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('has_payment_link')
                    ->label('Payment Link Status')
                    ->options([
                        'with' => 'Mit Payment Link',
                        'without' => 'Ohne Payment Link',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'with') {
                            return $query->whereNotNull('metadata->stripe_payment_link_url');
                        } elseif ($state['value'] === 'without') {
                            return $query->whereNull('metadata->stripe_payment_link_url');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('create_link')
                    ->label('Link erstellen')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(function ($record) {
                        $metadata = $record->metadata ?? [];
                        return !isset($metadata['stripe_payment_link_url']);
                    })
                    ->form([
                        Forms\Components\Radio::make('amount_type')
                            ->label('Betragstyp')
                            ->options([
                                'fixed' => 'Fester Betrag',
                                'variable' => 'Variabler Betrag',
                            ])
                            ->default('fixed')
                            ->reactive(),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Betrag (EUR)')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(5000)
                            ->default(100)
                            ->visible(fn ($get) => $get('amount_type') === 'fixed')
                            ->required(fn ($get) => $get('amount_type') === 'fixed'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $stripeService = app(StripeTopupService::class);
                            $amount = $data['amount_type'] === 'fixed' ? $data['amount'] : null;
                            
                            $paymentLinkUrl = $stripeService->createPaymentLink(
                                $record,
                                $amount,
                                ['created_by' => 'admin_panel']
                            );
                            
                            if ($paymentLinkUrl) {
                                Notification::make()
                                    ->title('Payment Link erstellt')
                                    ->body("Link: {$paymentLinkUrl}")
                                    ->success()
                                    ->persistent()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('copy')
                                            ->label('Link kopieren')
                                            ->action(function () use ($paymentLinkUrl) {
                                                // JavaScript to copy to clipboard
                                            }),
                                    ])
                                    ->send();
                            } else {
                                throw new \Exception('Failed to create payment link');
                            }
                        } catch (\Exception $e) {
                            Log::error('Payment link creation failed', [
                                'company_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                            
                            Notification::make()
                                ->title('Fehler')
                                ->body('Payment Link konnte nicht erstellt werden.')
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('view_link')
                    ->label('Link anzeigen')
                    ->icon('heroicon-o-eye')
                    ->visible(function ($record) {
                        $metadata = $record->metadata ?? [];
                        return isset($metadata['stripe_payment_link_url']);
                    })
                    ->modalContent(function ($record) {
                        $metadata = $record->metadata ?? [];
                        $url = $metadata['stripe_payment_link_url'] ?? '';
                        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
                        
                        return view('filament.admin.modals.payment-link-details', [
                            'url' => $url,
                            'qrUrl' => $qrUrl,
                            'company' => $record,
                        ]);
                    })
                    ->modalWidth('lg'),
                    
                Tables\Actions\Action::make('regenerate')
                    ->label('Neu generieren')
                    ->icon('heroicon-o-refresh')
                    ->color('warning')
                    ->visible(function ($record) {
                        $metadata = $record->metadata ?? [];
                        return isset($metadata['stripe_payment_link_url']);
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            $stripeService = app(StripeTopupService::class);
                            
                            // Clear old link from metadata
                            $metadata = $record->metadata ?? [];
                            unset($metadata['stripe_payment_link_url']);
                            unset($metadata['stripe_payment_link_id']);
                            unset($metadata['stripe_payment_link_created_at']);
                            $record->update(['metadata' => $metadata]);
                            
                            // Create new link
                            $paymentLinkUrl = $stripeService->createPaymentLink(
                                $record,
                                null, // Variable amount
                                ['created_by' => 'admin_panel_regenerate']
                            );
                            
                            if ($paymentLinkUrl) {
                                Notification::make()
                                    ->title('Payment Link neu generiert')
                                    ->body("Neuer Link: {$paymentLinkUrl}")
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception('Failed to regenerate payment link');
                            }
                        } catch (\Exception $e) {
                            Log::error('Payment link regeneration failed', [
                                'company_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                            
                            Notification::make()
                                ->title('Fehler')
                                ->body('Payment Link konnte nicht neu generiert werden.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('name');
    }
}