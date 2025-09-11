<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\BalanceTopup;
use Carbon\Carbon;

class BillingController extends Controller
{
    /**
     * Zeigt das Abrechnungs-Dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            abort(403, 'Kein Mandant mit diesem Benutzer verbunden');
        }

        $tenant = $user->tenant;
        
        // Sammle Dashboard-Daten
        $data = [
            'tenant' => $tenant,
            'balance' => $tenant->getFormattedBalance(),
            'transactions' => $tenant->transactions()
                ->latest()
                ->take(10)
                ->get(),
            'usage_today' => $this->getTodayUsage($tenant),
            'topup_amounts' => config('billing.topup_amounts', []),
        ];

        return view('billing.index', $data);
    }

    /**
     * Zeigt die Transaktionsliste
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            abort(403, 'Kein Mandant mit diesem Benutzer verbunden');
        }

        $query = $user->tenant->transactions();

        // Filter anwenden
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('billing.transactions', compact('transactions'));
    }

    /**
     * Zeigt die Auflade-Seite
     */
    public function topup(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            abort(403, 'Kein Mandant mit diesem Benutzer verbunden');
        }

        $topupAmounts = config('billing.topup_amounts', [2500, 5000, 10000]);
        
        return view('billing.topup', [
            'tenant' => $user->tenant,
            'amounts' => $topupAmounts,
            'current_balance' => $user->tenant->getFormattedBalance(),
        ]);
    }

    /**
     * Erstellt eine Stripe Checkout-Session
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1000|max:100000', // 10€ - 1000€
        ]);

        $user = $request->user();
        if (!$user || !$user->tenant) {
            abort(403, 'Kein Mandant mit diesem Benutzer verbunden');
        }

        // Stripe-Konfiguration prüfen
        $stripeSecret = config('billing.stripe.secret');
        if (!$stripeSecret) {
            Log::error('Stripe-Konfiguration fehlt');
            return back()->with('error', 'Zahlungssystem ist nicht konfiguriert. Bitte kontaktieren Sie den Support.');
        }

        try {
            Stripe::setApiKey($stripeSecret);

            $amountCents = (int) $request->amount;
            $amountEur = number_format($amountCents / 100, 2, ',', '.');

            // Erstelle Checkout-Session
            $session = StripeSession::create([
                'payment_method_types' => ['card', 'sepa_debit'],
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $amountCents,
                        'product_data' => [
                            'name' => "Guthaben-Aufladung {$amountEur}€",
                            'description' => "Prepaid-Guthaben für {$user->tenant->name}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.cancel'),
                'metadata' => [
                    'tenant_id' => $user->tenant->id,
                    'user_id' => $user->id,
                    'amount_cents' => $amountCents,
                ],
                'customer_email' => $user->email,
                'locale' => 'de',
            ]);

            // Erstelle vorläufigen Topup-Eintrag
            BalanceTopup::create([
                'tenant_id' => $user->tenant->id,
                'amount_cents' => $amountCents,
                'stripe_session_id' => $session->id,
                'status' => 'pending',
                'payment_method' => 'stripe',
                'metadata' => [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                ],
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            Log::error('Stripe Checkout-Fehler: ' . $e->getMessage());
            return back()->with('error', 'Fehler beim Erstellen der Zahlungssitzung. Bitte versuchen Sie es später erneut.');
        }
    }

    /**
     * Stripe Webhook-Handler mit verbesserter Sicherheit
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('billing.stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Stripe Webhook-Secret nicht konfiguriert');
            return response('Webhook-Konfiguration fehlt', 500);
        }

        try {
            // Verifiziere Webhook-Signatur
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
            
        } catch (SignatureVerificationException $e) {
            Log::warning('Ungültige Stripe Webhook-Signatur: ' . $e->getMessage());
            return response('Ungültige Signatur', 400);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook-Fehler: ' . $e->getMessage());
            return response('Webhook-Fehler', 400);
        }

        // Verarbeite Event basierend auf Typ
        DB::beginTransaction();
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutCompleted($event->data->object);
                    break;
                    
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;
                    
                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;
                    
                default:
                    Log::info('Unbehandelter Stripe-Event-Typ: ' . $event->type);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler bei Webhook-Verarbeitung: ' . $e->getMessage());
            return response('Verarbeitungsfehler', 500);
        }

        return response('OK', 200);
    }

    /**
     * Verarbeitet erfolgreiche Checkout-Sessions
     */
    private function handleCheckoutCompleted($session)
    {
        $tenantId = $session->metadata->tenant_id ?? null;
        $amountCents = $session->metadata->amount_cents ?? $session->amount_total;

        if (!$tenantId || !$amountCents) {
            Log::error('Checkout-Session ohne Tenant-ID oder Betrag', ['session' => $session->id]);
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            Log::error('Tenant nicht gefunden: ' . $tenantId);
            return;
        }

        // Aktualisiere Topup-Eintrag
        $topup = BalanceTopup::where('stripe_session_id', $session->id)->first();
        if ($topup) {
            $topup->update([
                'status' => 'completed',
                'completed_at' => now(),
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);
        } else {
            // Erstelle neuen Topup-Eintrag falls nicht vorhanden
            $topup = BalanceTopup::create([
                'tenant_id' => $tenantId,
                'amount_cents' => $amountCents,
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                'status' => 'completed',
                'completed_at' => now(),
                'payment_method' => 'stripe',
            ]);
        }

        // Füge Guthaben hinzu und erstelle Transaktion
        $tenant->addCredit(
            $amountCents,
            sprintf('Aufladung über Stripe (%s€)', number_format($amountCents / 100, 2, ',', '.'))
        );

        // Sende Bestätigungs-E-Mail (optional)
        if (config('billing.notifications.payment_success.email')) {
            // Mail::to($tenant->email)->send(new PaymentSuccessful($topup));
        }

        Log::info('Guthaben erfolgreich aufgeladen', [
            'tenant_id' => $tenantId,
            'amount_cents' => $amountCents,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Verarbeitet erfolgreiche Zahlungen
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        Log::info('Zahlung erfolgreich', ['payment_intent' => $paymentIntent->id]);
        // Weitere Verarbeitung falls nötig
    }

    /**
     * Verarbeitet fehlgeschlagene Zahlungen
     */
    private function handlePaymentFailed($paymentIntent)
    {
        Log::warning('Zahlung fehlgeschlagen', ['payment_intent' => $paymentIntent->id]);
        
        // Aktualisiere Topup-Status
        $topup = BalanceTopup::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if ($topup) {
            $topup->update(['status' => 'failed']);
        }
    }

    /**
     * Verarbeitet Rückerstattungen
     */
    private function handleChargeRefunded($charge)
    {
        Log::info('Rückerstattung erhalten', ['charge' => $charge->id]);
        
        // Finde zugehörige Transaktion und erstelle Rückerstattungs-Transaktion
        $topup = BalanceTopup::where('stripe_payment_intent_id', $charge->payment_intent)->first();
        if ($topup && $topup->tenant) {
            $refundAmount = $charge->amount_refunded;
            
            $topup->tenant->deductBalance(
                $refundAmount,
                sprintf('Rückerstattung für Aufladung (%s€)', number_format($refundAmount / 100, 2, ',', '.'))
            );
            
            $topup->update(['status' => 'refunded']);
        }
    }

    /**
     * Erfolgsseite nach Zahlung
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            $topup = BalanceTopup::where('stripe_session_id', $sessionId)->first();
            if ($topup && $topup->tenant_id === $request->user()->tenant_id) {
                return view('billing.success', [
                    'topup' => $topup,
                    'new_balance' => $topup->tenant->getFormattedBalance(),
                ]);
            }
        }

        return view('billing.success');
    }

    /**
     * Abbruchseite
     */
    public function cancel(Request $request)
    {
        return view('billing.cancel');
    }

    /**
     * Berechnet die heutige Nutzung
     */
    private function getTodayUsage(Tenant $tenant)
    {
        $usage = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'usage')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount_cents');

        return abs($usage);
    }
}
