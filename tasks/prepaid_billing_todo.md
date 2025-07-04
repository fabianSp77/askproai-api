# Prepaid Billing System mit Stripe - Implementation Plan

## 📋 Übersicht
Implementierung eines Prepaid-Guthaben-Systems mit sekundengenauer Abrechnung für Telefonate.

### Geschäftsmodell
- **Preis**: 0,42€ pro Minute (sekundengenau abgerechnet)
- **Zahlungsmodell**: Prepaid (Vorauszahlung)
- **Warnung**: Bei 20% Restguthaben
- **Zahlungsanbieter**: Stripe

## Phase 1: Datenbank-Struktur (Priority: HIGH) ✅ COMPLETE
- [x] Create prepaid_balances table (company_id, balance, reserved_balance)
- [x] Create balance_transactions table (type, amount, description, reference)
- [x] Create balance_topups table (stripe_payment_intent_id, amount, status)
- [x] Create billing_rates table (company_id, rate_per_minute, billing_increment)
- [x] Add balance_warning_sent_at to companies table
- [x] Create call_charges table (call_id, duration_seconds, amount_charged)

## Phase 2: Models & Relationships (Priority: HIGH) ✅ COMPLETE
- [x] Create PrepaidBalance Model mit atomic operations
- [x] Create BalanceTransaction Model mit Logging
- [x] Create BalanceTopup Model für Stripe
- [x] Create BillingRate Model
- [x] Create CallCharge Model
- [x] Add relationships und Scopes

## Phase 3: Stripe Integration (Priority: HIGH) ✅ COMPLETE
- [x] Create StripeTopupService für Zahlungen
- [x] Implement Payment Intent creation
- [x] Add Webhook handler für payment confirmations
- [x] Create Checkout Session für Aufladungen
- [x] Add Payment Method management (via Stripe)
- [x] Implement automatic receipts (via Stripe)

## Phase 4: Billing Service (Priority: HIGH) ✅ COMPLETE
- [x] Create PrepaidBillingService
- [x] Implement calculateCallCharge() method
- [x] Add chargeCall() mit atomic balance deduction
- [x] Create reserveBalance() für laufende Anrufe
- [x] Add releaseReservedBalance() nach Anruf
- [x] Implement getEffectiveBalance() (balance - reserved)

## Phase 5: Balance Monitoring (Priority: HIGH) ✅ COMPLETE
- [x] Create BalanceMonitoringService
- [x] Add checkLowBalance() method (20% threshold)
- [x] Implement sendLowBalanceWarning() 
- [x] Create scheduled command für Balance checks
- [x] Add balance check vor jedem Anruf
- [x] Block calls wenn Balance insufficient

## Phase 6: Portal Integration (Priority: HIGH) ✅ COMPLETE
- [x] Create BillingController für Portal
- [x] Add Balance Widget zum Dashboard (in billing index view)
- [x] Create Topup Page mit Stripe Checkout
- [x] Add Transaction History View
- [x] Create Usage Statistics Page
- [x] Add Download Invoice functionality (basic implementation)

## Phase 7: Notifications (Priority: MEDIUM)
- [ ] Create LowBalanceNotification
- [ ] Add Email template für 20% Warnung
- [ ] Include one-click topup link
- [ ] Create BalanceExhaustedNotification
- [ ] Add TopupSuccessfulNotification
- [ ] SMS notification option (optional)

## Phase 8: Admin Features (Priority: MEDIUM)
- [ ] Add manual balance adjustment
- [ ] Create billing reports
- [ ] Add rate management interface
- [ ] Implement credit notes
- [ ] Add billing audit log
- [ ] Export functionality

## Phase 9: Testing & Monitoring (Priority: MEDIUM)
- [ ] Unit tests für atomic operations
- [ ] Integration tests mit Stripe
- [ ] Test concurrent balance updates
- [ ] Add monitoring alerts
- [ ] Performance optimization
- [ ] Load testing

## Technische Details

### Sekundengenaue Abrechnung
```php
$pricePerMinute = 0.42;
$durationSeconds = 157; // 2:37 Minuten
$charge = ($durationSeconds / 60) * $pricePerMinute; // 1.099€
```

### Balance Check vor Anruf
```php
$minimumBalance = 0.42; // Mindestens 1 Minute
$effectiveBalance = $balance - $reservedBalance;
if ($effectiveBalance < $minimumBalance) {
    throw new InsufficientBalanceException();
}
```

### Atomic Balance Operations
```php
DB::transaction(function() {
    $balance->decrement('balance', $amount);
    $balance->increment('reserved_balance', $amount);
});
```

## Stripe Webhook Events
- `payment_intent.succeeded` - Guthaben aufladen
- `payment_intent.failed` - Zahlung fehlgeschlagen
- `charge.refunded` - Rückerstattung

## Security Considerations
- [ ] Validate all amounts (positive, max limits)
- [ ] Implement idempotency for charges
- [ ] Add rate limiting for topups
- [ ] Audit trail for all transactions
- [ ] PCI compliance for payment data

## Current Status
**Created**: 2025-07-03
**Updated**: 2025-07-03
**Status**: Portal Integration Complete
**Completed Phases**: 1-6 (Database, Models, Stripe, Billing Service, Monitoring, Portal)
**Next Steps**: 
1. Create email templates for low balance notifications
2. Test the complete billing flow end-to-end
3. Configure Stripe webhook in Stripe dashboard

## Implementation Summary

### What's Working:
- ✅ Database structure with all necessary tables
- ✅ Atomic balance operations to prevent race conditions
- ✅ Stripe integration for secure payments
- ✅ Billing service with second-precise calculations
- ✅ Balance monitoring with scheduled checks every 30 minutes
- ✅ Complete portal UI with billing dashboard, top-up, transactions, and usage views
- ✅ CSV export for usage reports
- ✅ Webhook endpoint for Stripe payment confirmations

### Stripe Configuration Required:
1. Add webhook endpoint in Stripe Dashboard: `https://api.askproai.de/api/stripe/webhook`
2. Configure webhook to listen for:
   - `payment_intent.succeeded`
   - `payment_intent.failed`
   - `checkout.session.completed`
   - `charge.refunded`
3. Copy webhook signing secret to `.env` as `STRIPE_WEBHOOK_SECRET`

### Testing Instructions:
1. Create test company with prepaid billing enabled
2. Set billing rate to 0.42€/minute
3. Test top-up flow with Stripe test cards
4. Make test calls to verify balance deduction
5. Verify low balance warning at 20% threshold