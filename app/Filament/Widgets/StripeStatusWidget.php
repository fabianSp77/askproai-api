<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Stripe\StripeClient;

class StripeStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.stripe-status-widget';

    public function getStripeData()
    {
        try {
            $stripe = new StripeClient(env('STRIPE_SECRET'));
            $balance = $stripe->balance->retrieve();
            $payments = $stripe->paymentIntents->all(['limit' => 6, 'order' => 'desc']);
            $latest = $payments->data[0] ?? null;

            return [
                'balance' => ($balance->available[0]->amount ?? 0) / 100,
                'currency' => strtoupper($balance->available[0]->currency ?? 'eur'),
                'last_payment' => $latest ? $latest->amount_received / 100 : null,
                'last_payment_at' => $latest && $latest->created ? date('d.m.Y H:i', $latest->created) : null,
                'last_payment_status' => $latest->status ?? null,
                'payment_chart' => array_map(function($p) {
                    return [
                        'amount' => $p->amount_received / 100,
                        'date'   => date('d.m.', $p->created),
                        'status' => $p->status
                    ];
                }, array_reverse($payments->data)),
                'errors' => [],
            ];
        } catch (\Exception $e) {
            return [
                'balance' => null,
                'currency' => null,
                'last_payment' => null,
                'last_payment_at' => null,
                'last_payment_status' => null,
                'payment_chart' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
