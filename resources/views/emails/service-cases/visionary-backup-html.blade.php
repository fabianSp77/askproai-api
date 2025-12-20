<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Backup: {{ $case->formatted_id }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f4f5; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 20px;">
                <table role="presentation" style="width: 100%; max-width: 700px; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                    {{-- ============================================================ --}}
                    {{-- SECTION 1: Header --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="padding: 30px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border-radius: 8px 8px 0 0;">
                            <table role="presentation" style="width: 100%;">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0 0 10px 0; color: #ffffff; font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">
                                            📦 Visionary Data Backup
                                        </h1>
                                        <h2 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; font-family: 'Courier New', monospace;">
                                            {{ $case->formatted_id }}
                                        </h2>
                                    </td>
                                    <td align="right" style="vertical-align: top;">
                                        <span style="display: inline-block; padding: 8px 16px; background-color: rgba(255,255,255,0.2); border-radius: 20px; color: #ffffff; font-size: 14px;">
                                            {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ============================================================ --}}
                    {{-- SECTION 2: Ticket-Basisdaten --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="padding: 25px 30px;">
                            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                                📋 Ticket-Basisdaten
                            </h3>

                            {{-- Status Badges --}}
                            <table role="presentation" style="width: 100%; margin-bottom: 15px;">
                                <tr>
                                    <td>
                                        {{-- Priority Badge --}}
                                        @php
                                            $priorityColors = [
                                                'critical' => '#DC2626',
                                                'high' => '#F59E0B',
                                                'normal' => '#3B82F6',
                                                'low' => '#6B7280',
                                            ];
                                            $priorityColor = $priorityColors[$case->priority] ?? '#6B7280';

                                            $statusColors = [
                                                'new' => '#9CA3AF',
                                                'open' => '#3B82F6',
                                                'pending' => '#F59E0B',
                                                'resolved' => '#10B981',
                                                'closed' => '#6366F1',
                                            ];
                                            $statusColor = $statusColors[$case->status] ?? '#6B7280';

                                            $typeLabels = [
                                                'incident' => '🚨 Störung',
                                                'request' => '📋 Anfrage',
                                                'inquiry' => '💬 Anfrage',
                                            ];
                                            $typeLabel = $typeLabels[$case->case_type] ?? '📞 Anliegen';
                                        @endphp

                                        <span style="display: inline-block; padding: 4px 12px; background-color: {{ $priorityColor }}; color: #ffffff; border-radius: 12px; font-size: 12px; font-weight: 600; margin-right: 8px;">
                                            {{ strtoupper($case->priority) }}
                                        </span>
                                        <span style="display: inline-block; padding: 4px 12px; background-color: {{ $statusColor }}; color: #ffffff; border-radius: 12px; font-size: 12px; font-weight: 600; margin-right: 8px;">
                                            {{ strtoupper($case->status) }}
                                        </span>
                                        <span style="display: inline-block; padding: 4px 12px; background-color: #E5E7EB; color: #374151; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            {{-- Subject & Description --}}
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">
                                        <strong style="color: #6b7280; font-size: 12px; text-transform: uppercase;">Betreff</strong><br>
                                        <span style="color: #1f2937; font-size: 16px; font-weight: 600;">{{ $case->subject }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">
                                        <strong style="color: #6b7280; font-size: 12px; text-transform: uppercase;">Beschreibung</strong><br>
                                        <span style="color: #374151; font-size: 14px; white-space: pre-wrap;">{{ $case->description }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0;">
                                        <table role="presentation" style="width: 100%;">
                                            <tr>
                                                <td style="width: 33%;">
                                                    <strong style="color: #6b7280; font-size: 12px; text-transform: uppercase;">Urgency</strong><br>
                                                    <span style="color: #1f2937;">{{ ucfirst($case->urgency) }}</span>
                                                </td>
                                                <td style="width: 33%;">
                                                    <strong style="color: #6b7280; font-size: 12px; text-transform: uppercase;">Impact</strong><br>
                                                    <span style="color: #1f2937;">{{ ucfirst($case->impact) }}</span>
                                                </td>
                                                <td style="width: 33%;">
                                                    <strong style="color: #6b7280; font-size: 12px; text-transform: uppercase;">Erstellt</strong><br>
                                                    <span style="color: #1f2937;">{{ $case->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- SLA Warning --}}
                            @if($case->isResponseOverdue() || $case->isResolutionOverdue())
                            <table role="presentation" style="width: 100%; margin-top: 15px;">
                                <tr>
                                    <td style="padding: 12px 15px; background-color: #FEF2F2; border-left: 4px solid #DC2626; border-radius: 4px;">
                                        <strong style="color: #DC2626;">⚠️ SLA ÜBERSCHRITTEN</strong>
                                        <br>
                                        <span style="color: #991B1B; font-size: 13px;">
                                            @if($case->isResponseOverdue())
                                                Response fällig: {{ $case->sla_response_due_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }}
                                            @endif
                                            @if($case->isResolutionOverdue())
                                                | Resolution fällig: {{ $case->sla_resolution_due_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            </table>
                            @endif
                        </td>
                    </tr>

                    {{-- ============================================================ --}}
                    {{-- SECTION 3: Kundendaten --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <table role="presentation" style="width: 100%; background-color: #EFF6FF; border-radius: 8px; border: 1px solid #BFDBFE;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 15px 0; color: #1e40af; font-size: 16px; font-weight: 600;">
                                            👤 Kundendaten
                                        </h3>
                                        @php
                                            $aiMetadata = $case->ai_metadata ?? [];
                                            $customerName = $aiMetadata['customer_name'] ?? $case->customer?->name ?? '-';
                                            $customerPhone = $aiMetadata['customer_phone'] ?? $case->customer?->phone ?? '-';
                                            $customerEmail = $case->customer?->email ?? '-';
                                            $customerLocation = $aiMetadata['customer_location'] ?? '-';
                                        @endphp
                                        <table role="presentation" style="width: 100%;">
                                            <tr>
                                                <td style="width: 50%; padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">NAME</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px;">{{ $customerName }}</span>
                                                </td>
                                                <td style="width: 50%; padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">TELEFON</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px; font-family: monospace;">{{ $customerPhone }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">E-MAIL</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px;">{{ $customerEmail }}</span>
                                                </td>
                                                <td style="padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">STANDORT</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px;">{{ $customerLocation }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ============================================================ --}}
                    {{-- SECTION 4: Anruf-Details --}}
                    {{-- ============================================================ --}}
                    @if($case->call)
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <table role="presentation" style="width: 100%; background-color: #F5F3FF; border-radius: 8px; border: 1px solid #DDD6FE;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 15px 0; color: #6d28d9; font-size: 16px; font-weight: 600;">
                                            📞 Anruf-Details
                                        </h3>
                                        <table role="presentation" style="width: 100%;">
                                            <tr>
                                                <td style="width: 50%; padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">CALL ID</strong><br>
                                                    <span style="color: #1f2937; font-size: 13px; font-family: monospace;">{{ $case->call->retell_call_id ?? '-' }}</span>
                                                </td>
                                                <td style="width: 50%; padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">DAUER</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px;">
                                                        @if($case->call->duration)
                                                            {{ floor($case->call->duration / 60) }}:{{ str_pad($case->call->duration % 60, 2, '0', STR_PAD_LEFT) }} min
                                                        @else
                                                            -
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">VON</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px; font-family: monospace;">{{ $case->call->from_number ?? '-' }}</span>
                                                </td>
                                                <td style="padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">AN</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px; font-family: monospace;">{{ $case->call->to_number ?? '-' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">ZEITPUNKT</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px;">
                                                        {{ $case->call->started_at ? $case->call->started_at->timezone('Europe/Berlin')->format('d.m.Y H:i:s') : '-' }}
                                                    </span>
                                                </td>
                                                <td style="padding: 5px 0;">
                                                    <strong style="color: #6b7280; font-size: 12px;">SENTIMENT</strong><br>
                                                    <span style="color: #1f2937; font-size: 15px;">{{ $case->call->sentiment ?? '-' }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- SECTION 5: KI-Zusammenfassung --}}
                    {{-- ============================================================ --}}
                    @if(!empty($aiMetadata['ai_summary']) || $case->call?->summary || !empty($aiMetadata['others_affected']))
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <table role="presentation" style="width: 100%; background-color: #ECFDF5; border-radius: 8px; border: 1px solid #A7F3D0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 15px 0; color: #047857; font-size: 16px; font-weight: 600;">
                                            🤖 KI-Analyse
                                        </h3>

                                        @if(!empty($aiMetadata['ai_summary']) || $case->call?->summary)
                                        <div style="margin-bottom: 12px;">
                                            <strong style="color: #6b7280; font-size: 12px;">ZUSAMMENFASSUNG</strong><br>
                                            <span style="color: #1f2937; font-size: 14px;">
                                                {{ $aiMetadata['ai_summary'] ?? $case->call?->summary ?? '-' }}
                                            </span>
                                        </div>
                                        @endif

                                        @if(!empty($aiMetadata['others_affected']))
                                        <div style="margin-bottom: 12px;">
                                            <strong style="color: #6b7280; font-size: 12px;">BETROFFENE PERSONEN/SYSTEME</strong><br>
                                            <span style="color: #1f2937; font-size: 14px;">{{ $aiMetadata['others_affected'] }}</span>
                                        </div>
                                        @endif

                                        @if(!empty($aiMetadata['additional_notes']))
                                        <div>
                                            <strong style="color: #6b7280; font-size: 12px;">ZUSÄTZLICHE NOTIZEN</strong><br>
                                            <span style="color: #1f2937; font-size: 14px;">{{ $aiMetadata['additional_notes'] }}</span>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- SECTION 6: Vollständiges Transkript --}}
                    {{-- ============================================================ --}}
                    @if($transcriptSegments->count() > 0)
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                                💬 {{ $transcriptTruncated ? 'Transkript (gekuerzt)' : 'Vollstaendiges Transkript' }} ({{ $transcriptSegments->count() }} Segmente)
                            </h3>

                            {{-- Truncation Warning --}}
                            @if($transcriptTruncated)
                            <table role="presentation" style="width: 100%; margin-bottom: 15px;">
                                <tr>
                                    <td style="padding: 12px 15px; background-color: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 4px;">
                                        <strong style="color: #92400E;">⚠️ Transkript gekuerzt</strong>
                                        <br>
                                        <span style="color: #78350F; font-size: 13px;">
                                            Das Transkript wurde auf {{ number_format($maxTranscriptChars) }} Zeichen gekuerzt
                                            (Original: {{ number_format($originalTranscriptLength) }} Zeichen).
                                            Vollstaendiges Transkript ueber API abrufbar.
                                        </span>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <table role="presentation" style="width: 100%; background-color: #F9FAFB; border-radius: 8px; border: 1px solid #E5E7EB;">
                                <tr>
                                    <td style="padding: 15px; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.8;">
                                        @foreach($transcriptSegments as $segment)
                                            @php
                                                $offsetSeconds = floor($segment->call_offset_ms / 1000);
                                                $minutes = floor($offsetSeconds / 60);
                                                $seconds = $offsetSeconds % 60;
                                                $timestamp = sprintf('[%02d:%02d]', $minutes, $seconds);

                                                $roleEmoji = $segment->role === 'agent' ? '🤖' : '👤';
                                                $roleLabel = $segment->role === 'agent' ? 'Agent' : 'Kunde';
                                                $roleColor = $segment->role === 'agent' ? '#3B82F6' : '#10B981';
                                            @endphp
                                            <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #E5E7EB;">
                                                <span style="color: #9CA3AF;">{{ $timestamp }}</span>
                                                <span style="color: {{ $roleColor }}; font-weight: 600;">{{ $roleEmoji }} {{ $roleLabel }}:</span>
                                                <span style="color: #374151;">{{ $segment->text }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- SECTION 7: Kategorie & Metadaten --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                                🏷️ Kategorie & Metadaten
                            </h3>

                            <table role="presentation" style="width: 100%;">
                                <tr>
                                    <td style="width: 50%; padding: 8px 0;">
                                        <strong style="color: #6b7280; font-size: 12px;">KATEGORIE</strong><br>
                                        <span style="color: #1f2937; font-size: 15px;">{{ $case->category?->name ?? '-' }}</span>
                                    </td>
                                    <td style="width: 50%; padding: 8px 0;">
                                        <strong style="color: #6b7280; font-size: 12px;">COMPANY</strong><br>
                                        <span style="color: #1f2937; font-size: 15px;">{{ $case->company?->name ?? '-' }}</span>
                                    </td>
                                </tr>
                                @if(!empty($aiMetadata['finalized_at']))
                                <tr>
                                    <td colspan="2" style="padding: 8px 0;">
                                        <strong style="color: #6b7280; font-size: 12px;">FINALISIERT AM</strong><br>
                                        <span style="color: #1f2937; font-size: 15px;">{{ $aiMetadata['finalized_at'] }}</span>
                                    </td>
                                </tr>
                                @endif
                            </table>

                            @if($case->structured_data && count($case->structured_data) > 0)
                            <div style="margin-top: 15px;">
                                <strong style="color: #6b7280; font-size: 12px;">STRUKTURIERTE DATEN</strong>
                                <table role="presentation" style="width: 100%; margin-top: 8px; background-color: #F9FAFB; border-radius: 4px;">
                                    @foreach($case->structured_data as $key => $value)
                                    <tr>
                                        <td style="padding: 6px 10px; border-bottom: 1px solid #E5E7EB; width: 30%;">
                                            <code style="color: #6366F1; font-size: 12px;">{{ $key }}</code>
                                        </td>
                                        <td style="padding: 6px 10px; border-bottom: 1px solid #E5E7EB;">
                                            <span style="color: #374151; font-size: 13px;">
                                                @if(is_array($value))
                                                    {{ json_encode($value) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                            @endif
                        </td>
                    </tr>

                    {{-- ============================================================ --}}
                    {{-- SECTION 8: JSON-Datenblock für maschinelle Verarbeitung --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                                📄 JSON-Datenblock (maschinenlesbar)
                            </h3>

                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 12px;">
                                Dieser Block enthält alle Ticket-Daten im JSON-Format für automatische Verarbeitung.
                                Die Marker <code>VISIONARY_DATA_JSON_START</code> und <code>VISIONARY_DATA_JSON_END</code> können zum Extrahieren verwendet werden.
                            </p>

                            <!-- VISIONARY_DATA_JSON_START -->
                            <pre style="background-color: #1F2937; color: #E5E7EB; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.5; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;">{{ $jsonString }}</pre>
                            <!-- VISIONARY_DATA_JSON_END -->
                        </td>
                    </tr>

                    {{-- ============================================================ --}}
                    {{-- Footer --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="padding: 20px 30px; background-color: #F9FAFB; border-radius: 0 0 8px 8px; border-top: 1px solid #E5E7EB;">
                            <table role="presentation" style="width: 100%;">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0; color: #9CA3AF; font-size: 12px;">
                                            📦 Automatisches Backup von AskProAI Service Gateway
                                        </p>
                                        <p style="margin: 5px 0 0 0; color: #9CA3AF; font-size: 11px;">
                                            Generiert: {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i:s') }} Uhr (Europe/Berlin)
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
