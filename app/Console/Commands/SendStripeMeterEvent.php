<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;

class SendStripeMeterEvent extends Command
{
    protected $signature = 'stripe:meter
                            {event_name           : Technischer Name des Zählers (z.B. api_requests)}
                            {customer_id          : Stripe-Customer-ID (cus_****)}
                            {quantity             : Nutzungseinheiten (Sekunden / Minuten)}
                            {--timestamp=         : Unix-Timestamp (Standard = jetzt)}
                            {--identifier=        : Optional eindeutige ID (wird sonst generiert)}';

    protected $description = 'Sendet ein Billing-Meter-Event an Stripe (Usage-based Billing, API v1)';

    public function handle(): int
    {
        /** 1) API-Key laden */
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }

        $stripe = new StripeClient($secret);

        /** 2) Argumente einlesen */
        $eventName  = $this->argument('event_name');
        $customerId = $this->argument('customer_id');
        $quantity   = (int) $this->argument('quantity');
        $timestamp  = $this->option('timestamp') ?: time();
        $identifier = $this->option('identifier');       // z.B. UUID – Stripe sorgt 24 h für Einzigartigkeit

        /** 3) Call an /v1/billing/meter_events */
        try {
            $resp = $stripe->billing->meterEvents->create([
                'event_name' => $eventName,
                'payload'    => [
                    'value'              => $quantity,
                    'stripe_customer_id' => $customerId,
                ],
                'timestamp'  => $timestamp,
                'identifier' => $identifier,
            ]);

            $this->info('✅  MeterEvent gesendet  →  '.$resp->id);
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
