<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title>Support-Ticket {{ $case->formatted_id }} - {{ $case->subject }}</title>
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
        /* Reset styles for email clients */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Mobile responsive styles */
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .fluid { max-width: 100% !important; height: auto !important; margin-left: auto !important; margin-right: auto !important; }
            .stack-column { display: block !important; width: 100% !important; max-width: 100% !important; }
            .mobile-padding { padding-left: 16px !important; padding-right: 16px !important; }
            .mobile-center { text-align: center !important; }
            .info-card { margin-bottom: 12px !important; }
            .json-block { font-size: 10px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <!-- Preheader Text (hidden preview text) -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Ticket {{ $case->formatted_id }} | {{ $priorityLabels[$case->priority] ?? 'Normal' }} | {{ $case->subject }}
    </div>

    @php
        // ================================================================
        // CONFIGURATION - Priority, Status, Type colors and labels
        // ================================================================
        $priorityColors = [
            'critical' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'border' => '#DC2626', 'label' => 'KRITISCH'],
            'high'     => ['bg' => '#FEF3C7', 'text' => '#D97706', 'border' => '#F59E0B', 'label' => 'HOCH'],
            'normal'   => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'border' => '#3B82F6', 'label' => 'NORMAL'],
            'low'      => ['bg' => '#F3F4F6', 'text' => '#4B5563', 'border' => '#9CA3AF', 'label' => 'NIEDRIG'],
        ];
        $pColor = $priorityColors[$case->priority] ?? $priorityColors['normal'];

        $statusColors = [
            'new'      => ['bg' => '#E0E7FF', 'text' => '#4338CA'],
            'open'     => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
            'pending'  => ['bg' => '#FEF3C7', 'text' => '#D97706'],
            'resolved' => ['bg' => '#D1FAE5', 'text' => '#059669'],
            'closed'   => ['bg' => '#F3F4F6', 'text' => '#374151'],
        ];
        $sColor = $statusColors[$case->status] ?? $statusColors['new'];

        $categoryColors = [
            'hardware'  => ['bg' => '#FEE2E2', 'text' => '#DC2626'],
            'software'  => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
            'network'   => ['bg' => '#F3E8FF', 'text' => '#7C3AED'],
            'email'     => ['bg' => '#FEF3C7', 'text' => '#D97706'],
            'security'  => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
            'other'     => ['bg' => '#F3F4F6', 'text' => '#374151'],
        ];

        // German translations
        $priorityLabels = ['critical' => 'Kritisch', 'high' => 'Hoch', 'normal' => 'Normal', 'low' => 'Niedrig'];
        $statusLabels = ['new' => 'Neu', 'open' => 'Offen', 'pending' => 'Wartend', 'resolved' => 'Geloest', 'closed' => 'Geschlossen'];

        // Extract data from ai_metadata (sanitized - no external provider references)
        $aiMeta = $case->ai_metadata ?? [];

        // Contact information
        $customerName = $aiMeta['customer_name'] ?? $case->customer?->name ?? 'Nicht angegeben';
        $customerPhone = $aiMeta['customer_phone'] ?? $case->customer?->phone ?? null;
        $customerEmail = $aiMeta['customer_email'] ?? $case->customer?->email ?? null;
        $customerLocation = $aiMeta['customer_location'] ?? $aiMeta['location'] ?? null;

        // Issue details
        $problemSince = $aiMeta['problem_since'] ?? null;
        $othersAffected = $aiMeta['others_affected'] ?? null;

        // Internal reference - rename retell_call_id to Anruf-Referenz
        $callReference = $aiMeta['retell_call_id'] ?? $aiMeta['call_id'] ?? $case->call?->id ?? null;

        // Build sanitized JSON for attachment (remove provider-specific keys)
        $sanitizedMeta = collect($aiMeta)->except(['retell_call_id', 'retell_agent_id'])->toArray();
        if ($callReference) {
            $sanitizedMeta['anruf_referenz'] = $callReference;
        }
        $jsonData = [
            'ticket_id' => $case->formatted_id,
            'internal_id' => $case->id,
            'erstellt_am' => $case->created_at->timezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            'prioritaet' => $case->priority,
            'status' => $case->status,
            'kategorie' => $case->category?->name ?? null,
            'betreff' => $case->subject,
            'beschreibung' => $case->description,
            'kontakt' => [
                'name' => $customerName,
                'telefon' => $customerPhone,
                'email' => $customerEmail,
                'standort' => $customerLocation,
            ],
            'problem_details' => [
                'seit' => $problemSince,
                'andere_betroffen' => $othersAffected,
            ],
            'metadaten' => $sanitizedMeta,
        ];
        $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Category color
        $categoryKey = strtolower($case->category?->slug ?? $case->category?->name ?? 'other');
        $catColor = $categoryColors[$categoryKey] ?? $categoryColors['other'];

        // Company branding
        $companyName = $case->company?->name ?? config('app.name', 'IT-Support');

        // Servicenummer / Angerufene Nummer Details
        $calledPhone = $case->call?->phoneNumber;
        $serviceNumber = $calledPhone?->formatted_number ?? $case->call?->to_number ?? null;
        $calledCompanyName = $calledPhone?->company?->name ?? null;
        $calledBranchName = $calledPhone?->branch?->name ?? null;
        $calledBranchPhone = $calledPhone?->branch?->phone_number ?? null;
        $callDuration = $case->call?->duration_formatted;

        // Format: "Berlin (+49 30 123456)" oder nur "AskPro GmbH"
        $receiverDisplay = null;
        if ($calledBranchName && $calledBranchPhone) {
            $receiverDisplay = $calledBranchName . ' (' . $calledBranchPhone . ')';
        } elseif ($calledBranchName && $calledCompanyName) {
            $receiverDisplay = $calledBranchName . ' (' . $calledCompanyName . ')';
        } elseif ($calledBranchName) {
            $receiverDisplay = $calledBranchName;
        } elseif ($calledCompanyName) {
            $receiverDisplay = $calledCompanyName;
        }
    @endphp

    <!-- Main Email Container -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <!-- Email Content Card -->
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="640" style="max-width: 640px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07);">

                    {{-- ================================================================
                         SECTION 1: QUICK INFO (Header)
                         - Ticket ID (prominent)
                         - Priority Badge (with color)
                         - Status Badge
                         - Category Badge
                         - Created Timestamp
                    ================================================================ --}}

                    <!-- Priority Header Bar -->
                    <tr>
                        <td style="background-color: {{ $pColor['bg'] }}; border-left: 6px solid {{ $pColor['border'] }}; padding: 14px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: {{ $pColor['text'] }}; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px;">
                                        {{ $pColor['label'] }} PRIORITAET
                                    </td>
                                    <td style="text-align: right; color: #6B7280; font-size: 12px;">
                                        {{ $case->created_at->timezone('Europe/Berlin')->format('d.m.Y') }} um {{ $case->created_at->timezone('Europe/Berlin')->format('H:i') }} Uhr
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Ticket ID Hero Section -->
                    <tr>
                        <td style="text-align: center; padding: 28px 24px 16px;">
                            <div style="font-family: 'SF Mono', Monaco, 'Courier New', Consolas, monospace; font-size: 32px; font-weight: 700; color: #1F2937; letter-spacing: 2px;">
                                {{ $case->formatted_id }}
                            </div>
                            <div style="color: #9CA3AF; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;">
                                Ticket-Nummer
                            </div>
                        </td>
                    </tr>

                    <!-- Status Badges Row -->
                    <tr>
                        <td style="text-align: center; padding: 0 24px 24px;">
                            <!-- Priority Badge -->
                            <span style="display: inline-block; background-color: {{ $pColor['bg'] }}; color: {{ $pColor['text'] }}; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px; border: 1px solid {{ $pColor['border'] }};">
                                {{ $priorityLabels[$case->priority] ?? $case->priority }}
                            </span>
                            <!-- Status Badge -->
                            <span style="display: inline-block; background-color: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px;">
                                {{ $statusLabels[$case->status] ?? $case->status }}
                            </span>
                            <!-- Category Badge -->
                            @if($case->category)
                            <span style="display: inline-block; background-color: {{ $catColor['bg'] }}; color: {{ $catColor['text'] }}; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px;">
                                {{ $case->category->name }}
                            </span>
                            @endif
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 24px;">
                            <div style="border-top: 1px solid #E5E7EB;"></div>
                        </td>
                    </tr>

                    {{-- ================================================================
                         SECTION 2: CONTACT INFO
                         - Customer Name
                         - Phone (clickable)
                         - Email (clickable)
                         - Location
                    ================================================================ --}}

                    <tr>
                        <td class="mobile-padding" style="padding: 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <!-- Section Header -->
                                        <div style="color: #1D4ED8; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #BFDBFE;">
                                            Kontaktinformationen
                                        </div>
                                        <!-- Contact Details Table -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <!-- Name -->
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 100px; vertical-align: top;">Name</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 600; padding: 6px 0;">{{ $customerName }}</td>
                                            </tr>
                                            <!-- Phone -->
                                            @if($customerPhone)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 100px; vertical-align: top;">Telefon</td>
                                                <td style="padding: 6px 0;">
                                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customerPhone) }}" style="color: #1D4ED8; font-size: 14px; font-weight: 500; text-decoration: none;">
                                                        {{ $customerPhone }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                            <!-- Email -->
                                            @if($customerEmail)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 100px; vertical-align: top;">E-Mail</td>
                                                <td style="padding: 6px 0;">
                                                    <a href="mailto:{{ $customerEmail }}" style="color: #1D4ED8; font-size: 14px; font-weight: 500; text-decoration: none;">
                                                        {{ $customerEmail }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                            <!-- Location -->
                                            @if($customerLocation)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 100px; vertical-align: top;">Standort</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 500; padding: 6px 0;">{{ $customerLocation }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ================================================================
                         SECTION 2.5: ANRUF-DETAILS
                         - Servicenummer (angerufene Nummer)
                         - Zugehöriges Unternehmen/Filiale
                         - Gesprächsdauer
                    ================================================================ --}}

                    @if($serviceNumber || $receiverDisplay || $callDuration)
                    <tr>
                        <td class="mobile-padding" style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <!-- Section Header -->
                                        <div style="color: #6D28D9; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #DDD6FE;">
                                            Anruf-Details
                                        </div>
                                        <!-- Call Details Table -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <!-- Servicenummer -->
                                            @if($serviceNumber)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 140px; vertical-align: top;">Servicenummer</td>
                                                <td style="padding: 6px 0;">
                                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $serviceNumber) }}" style="color: #6D28D9; font-size: 14px; font-weight: 500; text-decoration: none;">
                                                        {{ $serviceNumber }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                            <!-- Zugehöriges Unternehmen -->
                                            @if($receiverDisplay)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 140px; vertical-align: top;">Zugehöriges Unternehmen</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 500; padding: 6px 0;">{{ $receiverDisplay }}</td>
                                            </tr>
                                            @endif
                                            <!-- Gesprächsdauer -->
                                            @if($callDuration)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 140px; vertical-align: top;">Gesprächsdauer</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 500; padding: 6px 0;">{{ $callDuration }}</td>
                                            </tr>
                                            @endif
                                            <!-- Datum/Uhrzeit -->
                                            @if($case->call)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 140px; vertical-align: top;">Anruf-Zeitpunkt</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 500; padding: 6px 0;">
                                                    {{ $case->call->created_at->timezone('Europe/Berlin')->format('d.m.Y') }} um {{ $case->call->created_at->timezone('Europe/Berlin')->format('H:i') }} Uhr
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ================================================================
                         SECTION 3: ISSUE DETAILS
                         - Subject
                         - Description
                         - Problem Since
                         - Others Affected
                    ================================================================ --}}

                    <tr>
                        <td class="mobile-padding" style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <!-- Section Header -->
                                        <div style="color: #C2410C; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #FED7AA;">
                                            Problembeschreibung
                                        </div>

                                        <!-- Subject -->
                                        <div style="margin-bottom: 16px;">
                                            <div style="color: #78716C; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Betreff</div>
                                            <div style="color: #1F2937; font-size: 16px; font-weight: 600; line-height: 1.4;">{{ $case->subject }}</div>
                                        </div>

                                        <!-- Description -->
                                        <div style="margin-bottom: 16px;">
                                            <div style="color: #78716C; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Beschreibung</div>
                                            <div style="background-color: #FFFBEB; border-radius: 6px; padding: 14px; color: #374151; font-size: 14px; line-height: 1.6; white-space: pre-wrap; border: 1px solid #FDE68A;">{{ $case->description }}</div>
                                        </div>

                                        <!-- Problem Since -->
                                        @if($problemSince)
                                        <div style="margin-bottom: 12px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="color: #78716C; font-size: 12px; width: 120px; vertical-align: top;">Problem seit</td>
                                                    <td style="color: #1F2937; font-size: 14px; font-weight: 500;">{{ $problemSince }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                        @endif

                                        <!-- Others Affected -->
                                        @if($othersAffected !== null)
                                        <div>
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="color: #78716C; font-size: 12px; width: 120px; vertical-align: top;">Andere betroffen?</td>
                                                    <td style="font-size: 14px; font-weight: 500;">
                                                        @if($othersAffected === true || $othersAffected === 'true' || $othersAffected === 1 || $othersAffected === 'ja')
                                                            <span style="display: inline-block; background-color: #FEE2E2; color: #DC2626; padding: 3px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                                                Ja - Mehrere Mitarbeiter betroffen
                                                            </span>
                                                        @elseif($othersAffected === false || $othersAffected === 'false' || $othersAffected === 0 || $othersAffected === 'nein')
                                                            <span style="color: #059669;">Nein, nur diese Person</span>
                                                        @else
                                                            <span style="color: #1F2937;">{{ $othersAffected }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ================================================================
                         SECTION 4: TECHNICAL DATA
                         - JSON Data Block (code block style)
                         - Internal Reference IDs
                    ================================================================ --}}

                    <tr>
                        <td class="mobile-padding" style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <!-- Section Header -->
                                        <div style="color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #E2E8F0;">
                                            Technische Daten
                                        </div>

                                        <!-- Internal Reference IDs -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 16px;">
                                            <tr>
                                                <td style="color: #64748B; font-size: 12px; padding: 4px 0; width: 130px;">Interne ID</td>
                                                <td style="color: #1E293B; font-size: 12px; font-family: 'SF Mono', Monaco, 'Courier New', Consolas, monospace; padding: 4px 0;">{{ $case->id }}</td>
                                            </tr>
                                            @if($callReference)
                                            <tr>
                                                <td style="color: #64748B; font-size: 12px; padding: 4px 0; width: 130px;">Anruf-Referenz</td>
                                                <td style="color: #1E293B; font-size: 11px; font-family: 'SF Mono', Monaco, 'Courier New', Consolas, monospace; padding: 4px 0;">{{ $callReference }}</td>
                                            </tr>
                                            @endif
                                            @if($case->call?->id)
                                            <tr>
                                                <td style="color: #64748B; font-size: 12px; padding: 4px 0; width: 130px;">Call-ID</td>
                                                <td style="color: #1E293B; font-size: 12px; font-family: 'SF Mono', Monaco, 'Courier New', Consolas, monospace; padding: 4px 0;">{{ $case->call->id }}</td>
                                            </tr>
                                            @endif
                                        </table>

                                        <!-- JSON Data Block Header -->
                                        <div style="color: #64748B; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                            Vollstaendige Ticket-Daten (JSON)
                                        </div>

                                        <!-- JSON Code Block -->
                                        <div class="json-block" style="background-color: #1E293B; border-radius: 8px; padding: 16px; overflow-x: auto; max-height: 300px; overflow-y: auto;">
                                            <pre style="margin: 0; color: #E2E8F0; font-family: 'SF Mono', Monaco, 'Courier New', Consolas, monospace; font-size: 11px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">{{ $jsonString }}</pre>
                                        </div>

                                        <!-- Note about attachment -->
                                        <div style="margin-top: 12px; color: #94A3B8; font-size: 11px; font-style: italic;">
                                            Hinweis: Die vollstaendigen Ticket-Daten sind auch als JSON-Datei angehaengt.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Action Button -->
                    <tr>
                        <td style="text-align: center; padding: 8px 24px 28px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 8px; background-color: #2563EB;">
                                        <a href="{{ config('app.url') }}/admin/service-cases/{{ $case->id }}" style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 14px 36px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid #1D4ED8;">
                                            Ticket bearbeiten
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F8FAFC; padding: 18px 24px; border-top: 1px solid #E2E8F0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #94A3B8; font-size: 11px;">
                                        Gesendet: {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                    <td style="text-align: right; color: #64748B; font-size: 11px; font-weight: 500;">
                                        {{ $companyName }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- Footer Text -->
                <table role="presentation" class="email-container" width="640" cellpadding="0" cellspacing="0" border="0" style="margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; color: #94A3B8; font-size: 11px; line-height: 1.5;">
                            Automatische Benachrichtigung | {{ $companyName }} IT-Support
                            <br>
                            <span style="color: #CBD5E1;">Diese E-Mail wurde automatisch generiert.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
