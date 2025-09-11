<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - Aufladung #{{ $topup->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            background-color: #f4f4f4;
            padding: 20px;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
        }
        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 8px 0;
            font-weight: bold;
            color: #6b7280;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #1f2937;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-processing { background-color: #dbeafe; color: #1e40af; }
        .status-succeeded { background-color: #d1fae5; color: #065f46; }
        .status-failed { background-color: #fee2e2; color: #991b1b; }
        .status-cancelled { background-color: #f3f4f6; color: #4b5563; }
        .amount-display {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0;
        }
        .bonus-amount {
            color: #059669;
            font-size: 18px;
        }
        .total-amount {
            color: #3b82f6;
            font-size: 28px;
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ $title }}</h1>
        <div class="subtitle">Aufladung #{{ $topup->id }} vom {{ $topup->created_at->format('d.m.Y H:i:s') }}</div>
    </div>

    <div class="section">
        <h2 class="section-title">Status</h2>
        <span class="status-badge status-{{ $topup->status }}">
            @switch($topup->status)
                @case('pending') Ausstehend @break
                @case('processing') In Bearbeitung @break
                @case('succeeded') Erfolgreich @break
                @case('failed') Fehlgeschlagen @break
                @case('cancelled') Abgebrochen @break
                @default {{ $topup->status }}
            @endswitch
        </span>
    </div>

    <div class="section">
        <h2 class="section-title">Aufladungsdetails</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Betrag:</div>
                <div class="info-value amount-display">{{ number_format($topup->amount, 2) }} {{ $topup->currency }}</div>
            </div>
            @if($topup->bonus_amount > 0)
            <div class="info-row">
                <div class="info-label">Bonus:</div>
                <div class="info-value bonus-amount">+{{ number_format($topup->bonus_amount, 2) }} {{ $topup->currency }}</div>
            </div>
            @if($topup->bonus_reason)
            <div class="info-row">
                <div class="info-label">Bonus-Grund:</div>
                <div class="info-value">{{ $topup->bonus_reason }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Gesamtbetrag:</div>
                <div class="info-value total-amount">{{ number_format($topup->getTotalAmount(), 2) }} {{ $topup->currency }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Zahlungsmethode:</div>
                <div class="info-value">
                    @switch($topup->payment_method)
                        @case('stripe') Stripe (Kreditkarte) @break
                        @case('bank_transfer') Banküberweisung @break
                        @case('manual') Manuelle Aufladung @break
                        @case('bonus') Bonus/Gutschrift @break
                        @case('trial') Testguthaben @break
                        @default {{ $topup->payment_method }}
                    @endswitch
                </div>
            </div>
            @if($topup->paid_at)
            <div class="info-row">
                <div class="info-label">Zahlungsdatum:</div>
                <div class="info-value">{{ $topup->paid_at->format('d.m.Y H:i:s') }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($tenant)
    <div class="section">
        <h2 class="section-title">Tenant-Informationen</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Tenant:</div>
                <div class="info-value">{{ $tenant->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Aktuelles Guthaben:</div>
                <div class="info-value">{{ number_format($tenant->balance_cents / 100, 2) }} €</div>
            </div>
        </div>
    </div>
    @endif

    @if($topup->stripe_payment_intent_id || $topup->stripe_checkout_session_id)
    <div class="section">
        <h2 class="section-title">Zahlungsreferenzen</h2>
        <div class="info-grid">
            @if($topup->stripe_payment_intent_id)
            <div class="info-row">
                <div class="info-label">Stripe Payment Intent:</div>
                <div class="info-value" style="font-family: monospace; font-size: 10px;">{{ $topup->stripe_payment_intent_id }}</div>
            </div>
            @endif
            @if($topup->stripe_checkout_session_id)
            <div class="info-row">
                <div class="info-label">Stripe Checkout Session:</div>
                <div class="info-value" style="font-family: monospace; font-size: 10px;">{{ $topup->stripe_checkout_session_id }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Generiert am {{ $generatedAt }}</p>
        <p>Dieses Dokument wurde automatisch erstellt und ist ohne Unterschrift gültig.</p>
    </div>
</body>
</html>