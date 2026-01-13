<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
    <title>Rechnung {{ $invoice->invoice_number }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
        }
        u + #body a {
            color: inherit;
            text-decoration: none;
        }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .mobile-padding { padding: 24px 20px !important; }
            .mobile-padding-sm { padding: 16px !important; }
            .mobile-center { text-align: center !important; }
            .invoice-number { font-size: 22px !important; }
            .total-amount { font-size: 32px !important; }
            .cta-button { width: 100% !important; display: block !important; }
            .info-row td { display: block !important; width: 100% !important; padding: 8px 0 !important; }
        }
    </style>
</head>
<body id="body" style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <!-- Preheader -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Rechnung {{ $invoice->invoice_number }} | {{ $invoice->formatted_total }} | {{ $invoice->billing_period_display }}
    </div>

    @php
        $statusConfig = [
            'draft' => ['bg' => '#F3F4F6', 'text' => '#374151', 'label' => 'Entwurf'],
            'open' => ['bg' => '#FEF3C7', 'text' => '#D97706', 'label' => 'Offen'],
            'paid' => ['bg' => '#D1FAE5', 'text' => '#059669', 'label' => 'Bezahlt'],
            'void' => ['bg' => '#E5E7EB', 'text' => '#6B7280', 'label' => 'Storniert'],
            'uncollectible' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'label' => 'Uneinbringlich'],
        ];
        $sConfig = $statusConfig[$invoice->status] ?? $statusConfig['open'];
        $isOverdue = $invoice->status === 'open' && $invoice->due_at && $invoice->due_at->isPast();
        if ($isOverdue) {
            $sConfig = ['bg' => '#FEE2E2', 'text' => '#DC2626', 'label' => 'Ueberfaellig'];
        }
        $formatCurrency = fn($amount) => number_format($amount, 2, ',', '.') . ' EUR';
    @endphp

    <center style="width: 100%; background-color: #f8fafc;">
        <!--[if mso | IE]>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc;">
        <tr><td>
        <![endif]-->

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; padding: 0; background-color: #f8fafc;">
            <tr>
                <td style="padding: 40px 20px;">

                    <!-- Email Container -->
                    <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="560" style="margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; max-width: 560px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">

                        <!-- ============================================================ -->
                        <!-- UNIFIED HERO - Brand + Invoice + Total -->
                        <!-- ============================================================ -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); padding: 0;">
                                <!--[if gte mso 9]>
                                <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:560px;">
                                <v:fill type="gradient" color="#1e3a5f" color2="#2563eb" angle="135" />
                                <v:textbox inset="0,0,0,0">
                                <![endif]-->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <!-- Top Bar -->
                                    <tr>
                                        <td style="padding: 20px 28px 0 28px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="color: rgba(255,255,255,0.9); font-size: 13px; font-weight: 600; letter-spacing: 0.3px;">
                                                        AskProAI
                                                    </td>
                                                    <td style="text-align: right;">
                                                        <span style="display: inline-block; padding: 5px 12px; background-color: {{ $sConfig['bg'] }}; color: {{ $sConfig['text'] }}; border-radius: 16px; font-size: 11px; font-weight: 600;">
                                                            {{ $sConfig['label'] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <!-- Invoice Number + Total (Unified) -->
                                    <tr>
                                        <td style="text-align: center; padding: 28px 28px 12px 28px;">
                                            <p style="margin: 0 0 4px 0; color: rgba(255,255,255,0.6); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px;">
                                                Rechnung
                                            </p>
                                            <h1 class="invoice-number" style="margin: 0 0 20px 0; color: #ffffff; font-size: 24px; font-weight: 600; font-family: 'SF Mono', Monaco, 'Courier New', monospace; letter-spacing: 0.5px;">
                                                {{ $invoice->invoice_number }}
                                            </h1>
                                            <p class="total-amount" style="margin: 0; color: #ffffff; font-size: 38px; font-weight: 700; letter-spacing: -1px;">
                                                {{ $invoice->formatted_total }}
                                            </p>
                                        </td>
                                    </tr>

                                    <!-- Due Date / Period -->
                                    <tr>
                                        <td style="text-align: center; padding: 0 28px 24px 28px;">
                                            @if($invoice->due_at && $invoice->status !== 'paid')
                                            <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 13px;">
                                                Faellig am <strong style="color: #ffffff;">{{ $invoice->due_at->format('d. F Y') }}</strong>
                                            </p>
                                            @else
                                            <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 13px;">
                                                {{ $invoice->billing_period_display }}
                                            </p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                                <!--[if gte mso 9]>
                                </v:textbox>
                                </v:rect>
                                <![endif]-->
                            </td>
                        </tr>

                        <!-- ============================================================ -->
                        <!-- OVERDUE WARNING (direkt nach Hero wenn ueberfaellig) -->
                        <!-- ============================================================ -->
                        @if($isOverdue)
                        <tr>
                            <td style="padding: 20px 28px 0 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FEF2F2; border-left: 4px solid #DC2626; border-radius: 0 8px 8px 0;">
                                    <tr>
                                        <td style="padding: 14px 16px;">
                                            <p style="margin: 0; color: #991B1B; font-size: 13px; line-height: 1.5;">
                                                <strong>Zahlungserinnerung:</strong> Diese Rechnung war am {{ $invoice->due_at->format('d.m.Y') }} faellig.
                                                Bitte begleichen Sie den Betrag umgehend.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif

                        <!-- ============================================================ -->
                        <!-- GREETING (personalisiert wenn moeglich) -->
                        <!-- ============================================================ -->
                        <tr>
                            <td class="mobile-padding" style="padding: 28px 28px 20px 28px;">
                                <p style="margin: 0 0 12px 0; color: #374151; font-size: 15px; line-height: 1.6;">
                                    @if($partner->partner_billing_name)
                                    Sehr geehrte/r {{ $partner->partner_billing_name }},
                                    @else
                                    Sehr geehrte Damen und Herren,
                                    @endif
                                </p>
                                <p style="margin: 0; color: #6B7280; font-size: 14px; line-height: 1.6;">
                                    anbei erhalten Sie die Sammelrechnung fuer
                                    <strong style="color: #374151;">{{ $partner->name }}</strong>
                                    fuer den Abrechnungszeitraum
                                    <strong style="color: #374151;">{{ $invoice->billing_period_display }}</strong>.
                                </p>
                            </td>
                        </tr>

                        <!-- ============================================================ -->
                        <!-- DETAILS SECTION (clean, minimal) -->
                        <!-- ============================================================ -->
                        <tr>
                            <td style="padding: 0 28px 20px 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border: 1px solid #E5E7EB; border-radius: 8px;">
                                    <!-- Rechnungsdetails -->
                                    <tr>
                                        <td style="padding: 16px 18px; border-bottom: 1px solid #E5E7EB;">
                                            <p style="margin: 0 0 10px 0; color: #6B7280; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;">
                                                Rechnungsdetails
                                            </p>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr class="info-row">
                                                    <td style="color: #9CA3AF; font-size: 13px; padding-bottom: 4px;" width="35%">Rechnungsnr.</td>
                                                    <td style="color: #1F2937; font-size: 13px; font-weight: 500; padding-bottom: 4px;">{{ $invoice->invoice_number }}</td>
                                                </tr>
                                                <tr class="info-row">
                                                    <td style="color: #9CA3AF; font-size: 13px; padding-bottom: 4px;">Zeitraum</td>
                                                    <td style="color: #1F2937; font-size: 13px; padding-bottom: 4px;">{{ $invoice->billing_period_start->format('d.m.') }} - {{ $invoice->billing_period_end->format('d.m.Y') }}</td>
                                                </tr>
                                                <tr class="info-row">
                                                    <td style="color: #9CA3AF; font-size: 13px;">Positionen</td>
                                                    <td style="color: #1F2937; font-size: 13px;">
                                                        {{ $itemCount }} Positionen
                                                        @if($invoice->stripe_pdf_url)
                                                        <a href="{{ $invoice->stripe_pdf_url }}" style="color: #2563EB; text-decoration: none; margin-left: 8px; font-size: 12px;">(PDF)</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- Betragsdetails -->
                                    <tr>
                                        <td style="padding: 16px 18px;">
                                            <p style="margin: 0 0 10px 0; color: #6B7280; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;">
                                                Betrag
                                            </p>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr class="info-row">
                                                    <td style="color: #9CA3AF; font-size: 13px; padding-bottom: 4px;" width="35%">Netto</td>
                                                    <td style="color: #1F2937; font-size: 13px; padding-bottom: 4px;">{{ $formatCurrency($invoice->subtotal) }}</td>
                                                </tr>
                                                @if($invoice->discount > 0)
                                                <tr class="info-row">
                                                    <td style="color: #059669; font-size: 13px; padding-bottom: 4px;">Rabatt</td>
                                                    <td style="color: #059669; font-size: 13px; padding-bottom: 4px;">-{{ $formatCurrency($invoice->discount) }}</td>
                                                </tr>
                                                @endif
                                                <tr class="info-row">
                                                    <td style="color: #9CA3AF; font-size: 13px; padding-bottom: 8px;">MwSt. ({{ number_format($invoice->tax_rate, 0) }}%)</td>
                                                    <td style="color: #1F2937; font-size: 13px; padding-bottom: 8px;">{{ $formatCurrency($invoice->tax) }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="color: #1F2937; font-size: 14px; font-weight: 600; padding-top: 8px; border-top: 1px solid #E5E7EB;">Gesamt</td>
                                                    <td style="color: #1F2937; font-size: 14px; font-weight: 700; padding-top: 8px; border-top: 1px solid #E5E7EB;">{{ $invoice->formatted_total }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- ============================================================ -->
                        <!-- NOTES (wenn vorhanden) -->
                        <!-- ============================================================ -->
                        @if($invoice->notes)
                        <tr>
                            <td style="padding: 0 28px 20px 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFFBEB; border-left: 3px solid #F59E0B; border-radius: 0 6px 6px 0;">
                                    <tr>
                                        <td style="padding: 12px 14px;">
                                            <p style="margin: 0; color: #92400E; font-size: 13px; line-height: 1.5;">
                                                {{ $invoice->notes }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif

                        <!-- ============================================================ -->
                        <!-- CTA BUTTON (optimiert) -->
                        <!-- ============================================================ -->
                        @if($invoice->stripe_hosted_invoice_url && $invoice->status !== 'paid')
                        <tr>
                            <td style="padding: 8px 28px 16px 28px; text-align: center;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                    <tr>
                                        <td style="border-radius: 8px; background: linear-gradient(135deg, #059669 0%, #10B981 100%);">
                                            <!--[if mso]>
                                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $invoice->stripe_hosted_invoice_url }}" style="height:48px;v-text-anchor:middle;width:200px;" arcsize="17%" strokecolor="#059669" fillcolor="#059669">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:600;">Jetzt bezahlen</center>
                                            </v:roundrect>
                                            <![endif]-->
                                            <!--[if !mso]><!-->
                                            <a href="{{ $invoice->stripe_hosted_invoice_url }}" class="cta-button" style="display: inline-block; background: linear-gradient(135deg, #059669 0%, #10B981 100%); color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; text-align: center;">
                                                Jetzt bezahlen
                                            </a>
                                            <!--<![endif]-->
                                        </td>
                                    </tr>
                                </table>

                                <!-- Trust Signals -->
                                <p style="margin: 14px 0 0 0; color: #9CA3AF; font-size: 11px; line-height: 1.5;">
                                    256-bit SSL verschluesselt · PCI-DSS konform · Sichere Zahlung via Stripe
                                </p>

                                <!-- Secondary Link -->
                                @if($invoice->stripe_pdf_url)
                                <p style="margin: 10px 0 0 0; font-size: 12px;">
                                    <a href="{{ $invoice->stripe_pdf_url }}" style="color: #6B7280; text-decoration: none;">PDF ansehen</a>
                                </p>
                                @endif
                            </td>
                        </tr>
                        @elseif($invoice->status === 'paid')
                        <tr>
                            <td style="padding: 8px 28px 24px 28px; text-align: center;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px;">
                                    <tr>
                                        <td style="padding: 18px; text-align: center;">
                                            <p style="margin: 0 0 4px 0; color: #059669; font-size: 15px; font-weight: 600;">
                                                Bezahlt am {{ $invoice->paid_at?->format('d.m.Y') }}
                                            </p>
                                            <p style="margin: 0; color: #6B7280; font-size: 13px;">
                                                Vielen Dank fuer Ihre Zahlung!
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif

                        <!-- ============================================================ -->
                        <!-- DETAILED BREAKDOWN BY CUSTOMER -->
                        <!-- ============================================================ -->
                        @php
                            $allItems = $invoice->items()->with('company')->get();
                            $itemsByCompany = $allItems->groupBy('company_id');
                            $maxCompaniesShown = 5;
                            $showAllCompanies = $itemsByCompany->count() <= $maxCompaniesShown;
                            $displayedCompanies = $showAllCompanies ? $itemsByCompany : $itemsByCompany->take($maxCompaniesShown);
                            $hiddenCompanyCount = $itemsByCompany->count() - $maxCompaniesShown;
                        @endphp

                        @if($allItems->isNotEmpty())
                        <tr>
                            <td style="padding: 20px 28px 8px 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="border-bottom: 2px solid #E5E7EB; padding-bottom: 10px;">
                                            <p style="margin: 0; color: #374151; font-size: 13px; font-weight: 600;">
                                                Aufschluesselung nach Kunden
                                            </p>
                                            <p style="margin: 3px 0 0 0; color: #9CA3AF; font-size: 11px;">
                                                {{ $itemsByCompany->count() }} {{ $itemsByCompany->count() === 1 ? 'Kunde' : 'Kunden' }} · {{ $allItems->count() }} Positionen
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        @foreach($displayedCompanies as $companyId => $companyItems)
                        @php
                            $customerCompany = $companyItems->first()->company;
                            $companyTotal = $companyItems->sum('amount_cents') / 100;
                        @endphp
                        <tr>
                            <td style="padding: 6px 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 6px; border: 1px solid #E5E7EB;">
                                    <!-- Company Header -->
                                    <tr>
                                        <td style="padding: 12px 14px 8px 14px; border-bottom: 1px solid #E5E7EB;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="color: #1F2937; font-size: 13px; font-weight: 600;">
                                                        {{ $customerCompany?->name ?? 'Allgemein' }}
                                                    </td>
                                                    <td style="text-align: right; color: #374151; font-size: 13px; font-weight: 600;">
                                                        {{ number_format($companyTotal, 2, ',', '.') }} EUR
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- Line Items -->
                                    @foreach($companyItems as $item)
                                    <tr>
                                        <td style="padding: 8px 14px; {{ !$loop->last ? 'border-bottom: 1px solid #F3F4F6;' : '' }}">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="color: #6B7280; font-size: 12px; vertical-align: top; width: 60%;">
                                                        {{ $item->description }}
                                                    </td>
                                                    <td style="color: #9CA3AF; font-size: 11px; text-align: center; vertical-align: top; width: 18%;">
                                                        @if($item->quantity && $item->unit)
                                                        {{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2, ',', '.') }} {{ $item->unit }}
                                                        @else
                                                        1x
                                                        @endif
                                                    </td>
                                                    <td style="color: #374151; font-size: 12px; text-align: right; font-weight: 500; vertical-align: top; width: 22%;">
                                                        {{ $item->formatted_amount }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                        @endforeach

                        @if(!$showAllCompanies)
                        <tr>
                            <td style="padding: 6px 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FEF3C7; border-radius: 6px;">
                                    <tr>
                                        <td style="padding: 10px 14px; text-align: center;">
                                            <p style="margin: 0; color: #92400E; font-size: 12px;">
                                                ... und {{ $hiddenCompanyCount }} weitere {{ $hiddenCompanyCount === 1 ? 'Kunde' : 'Kunden' }}
                                                @if($invoice->stripe_pdf_url)
                                                <a href="{{ $invoice->stripe_pdf_url }}" style="color: #D97706; text-decoration: underline; margin-left: 6px;">Alle Details (PDF)</a>
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif

                        <!-- Grand Total -->
                        <tr>
                            <td style="padding: 12px 28px 16px 28px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="border-top: 2px solid #E5E7EB; padding-top: 10px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="color: #6B7280; font-size: 12px;">
                                                        Summe ({{ $itemsByCompany->count() }} {{ $itemsByCompany->count() === 1 ? 'Kunde' : 'Kunden' }})
                                                    </td>
                                                    <td style="text-align: right; color: #1F2937; font-size: 14px; font-weight: 700;">
                                                        {{ $invoice->formatted_total }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif

                        <!-- ============================================================ -->
                        <!-- SIGNATURE -->
                        <!-- ============================================================ -->
                        <tr>
                            <td style="padding: 8px 28px 24px 28px;">
                                <p style="margin: 0; color: #6B7280; font-size: 14px;">Mit freundlichen Gruessen</p>
                            </td>
                        </tr>

                        <!-- ============================================================ -->
                        <!-- FOOTER -->
                        <!-- ============================================================ -->
                        <tr>
                            <td style="padding: 20px 28px; background-color: #F9FAFB; border-top: 1px solid #E5E7EB;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="text-align: center;">
                                            <p style="margin: 0 0 2px 0; color: #374151; font-size: 13px; font-weight: 600;">
                                                AskProAI
                                            </p>
                                            <p style="margin: 0 0 8px 0; color: #6B7280; font-size: 12px;">
                                                Fabian Spitzer · George-Stephenson-Strasse 12 · 10557 Berlin
                                            </p>
                                            <p style="margin: 0; font-size: 12px;">
                                                <a href="tel:+491604366218" style="color: #6B7280; text-decoration: none;">+49 160 4366218</a>
                                                <span style="color: #D1D5DB; margin: 0 6px;">|</span>
                                                <a href="mailto:fabian@askproai.de" style="color: #2563EB; text-decoration: none;">fabian@askproai.de</a>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; padding-top: 14px; border-top: 1px solid #E5E7EB; margin-top: 14px;">
                                            <p style="margin: 0; color: #9CA3AF; font-size: 10px; line-height: 1.5;">
                                                Diese E-Mail wurde automatisch generiert · Ref: {{ $invoice->invoice_number }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                    <!-- End Email Container -->

                </td>
            </tr>
        </table>

        <!--[if mso | IE]>
        </td></tr></table>
        <![endif]-->
    </center>
</body>
</html>
