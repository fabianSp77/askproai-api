<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;

class SendStripeMeterEvent extends Command
{
    protected $signature = 'stripe:meter
                            {subscription_item : z.B. si_SGyycOxUig4SRf}
                            {quantity          : Nutzungseinheiten (Sek., Min.)}
                            {--timestamp=      : Unix-Timestamp (Standard: now)}';

    protected $description = 'Sendet ein Meter-Event an Stripe (Billing Meter API)';

    public function handle(): int
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }

        $stripe = new StripeClient($secret);

        $itemId    = $this->argument('subscription_item');
        $quantity  = (int) $this->argument('quantity');
        $timestamp = (int) ($this->option('timestamp') ?: time());

        try {
            /**  Billing Meter Events API (v2023-10-16)  */
            $event = $stripe->billing->meterEvents->create([
                'subscription_item' => $itemId,
                'quantity'          => $quantity,
                'timestamp'         => $timestamp,
            ]);

            $this->info('✅  MeterEvent gesendet  →  '.$event->id);
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
