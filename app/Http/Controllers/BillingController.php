<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as Checkout;
use Stripe\Webhook;
use App\Models\Tenant;

class BillingController extends Controller
{
    // 3a)  Checkout-Link (GET /billing/checkout)
    public function checkout(Request $r)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Checkout::create([
            'payment_method_types' => ['card'],
            'mode'   => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => 5000, // 50 € in Cent
                    'product_data' => ['name' => 'Prepaid-Guthaben 50 €'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => url('/billing/success'),
            'cancel_url'  => url('/billing/cancel'),
            'metadata'    => ['tenant_id' => $r->user()->tenant_id],
        ]);

        return redirect($session->url);
    }

    // 3b)  Webhook-Endpoint  POST /billing/webhook
    public function webhook(Request $r)
    {
        $payload = $r->getContent();
        $sig     = $r->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('Invalid', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $tid = $event->data->object->metadata->tenant_id ?? null;
            if ($tid && ($tenant = Tenant::find($tid))) {
                // +50 € Guthaben
                $tenant->increment('balance_cents', 5000);
            }
        }

        return response('OK', 200);
    }
}
