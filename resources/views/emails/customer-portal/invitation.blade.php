<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="date=no" />
    <meta name="format-detection" content="address=no" />
    <meta name="format-detection" content="email=no" />
    <title>Einladung zum Kundenportal</title>
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
        /* Client-specific Styles */
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
        /* iOS Blue Links */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }
        /* Gmail Blue Links */
        u + #body a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }
        /* Mobile Styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: auto !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            .mobile-padding {
                padding: 20px !important;
            }
            .mobile-font-size {
                font-size: 24px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <center style="width: 100%; background-color: #f3f4f6;">
        <!--[if mso | IE]>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
        <td>
        <![endif]-->

        <!-- Preheader Text (Hidden) -->
        <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
            Sie wurden zum Kundenportal von {{ $invitation->company->name ?? config('app.name') }} eingeladen
        </div>

        <!-- Outer Wrapper Table -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; padding: 0; background-color: #f3f4f6;">
            <tr>
                <td style="padding: 40px 20px;">
                    <!-- Email Container -->
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; max-width: 600px;">

                        <!-- Header with Gradient -->
                        <tr>
                            <td style="background-color: #2563eb; background-image: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); padding: 40px; text-align: center;">
                                <!--[if gte mso 9]>
                                <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:200px;">
                                <v:fill type="gradient" color="#2563eb" color2="#7c3aed" angle="135" />
                                <v:textbox inset="0,0,0,0">
                                <![endif]-->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="text-align: center;">
                                            <!-- Icon Circle -->
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto 16px auto;">
                                                <tr>
                                                    <td style="width: 70px; height: 70px; background-color: rgba(255,255,255,0.2); border-radius: 50%; text-align: center; line-height: 70px; font-size: 32px;">
                                                        ✉️
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- Title -->
                                            <h1 style="margin: 0 0 8px 0; color: #ffffff; font-size: 28px; font-weight: 700; line-height: 1.3; letter-spacing: -0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Willkommen bei {{ $invitation->company->name ?? config('app.name', 'AskPro') }}!
                                            </h1>
                                            <!-- Subtitle -->
                                            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 16px; line-height: 1.5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Sie wurden zum Kundenportal eingeladen
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                <!--[if gte mso 9]>
                                </v:textbox>
                                </v:rect>
                                <![endif]-->
                            </td>
                        </tr>

                        <!-- Content Section -->
                        <tr>
                            <td class="mobile-padding" style="padding: 40px;">
                                <!-- Greeting -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-bottom: 24px;">
                                            <p style="margin: 0; font-size: 18px; color: #374151; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Hallo,
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Intro Text -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-bottom: 24px;">
                                            <p style="margin: 0; font-size: 16px; color: #4b5563; line-height: 1.7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                <strong style="color: #1f2937;">{{ $invitation->inviter->name ?? 'Ein Mitarbeiter' }}</strong> von
                                                <strong style="color: #1f2937;">{{ $invitation->company->name ?? 'unserem Unternehmen' }}</strong>
                                                hat Sie eingeladen, unser Kundenportal zu nutzen. Dort können Sie Ihre Termine
                                                bequem online verwalten.
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Personal Message (if exists) -->
                                @if(isset($invitation->metadata['personal_message']) && !empty($invitation->metadata['personal_message']))
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 20px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; margin-bottom: 24px;">
                                            <p style="margin: 0 0 8px 0; font-weight: 600; color: #92400e; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Persönliche Nachricht
                                            </p>
                                            <p style="margin: 0; color: #78350f; font-size: 15px; font-style: italic; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                "{{ $invitation->metadata['personal_message'] }}"
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                <!-- Spacer -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-top: 24px;"></td>
                                    </tr>
                                </table>
                                @endif

                                <!-- Role Info Card -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 20px; background-color: #faf5ff; border-left: 4px solid #8b5cf6; border-radius: 8px;">
                                            <p style="margin: 0 0 8px 0; font-weight: 600; color: #1f2937; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                📋 Ihre Zugangsrolle: {{ $invitation->getRoleDisplayName() }}
                                            </p>
                                            <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                @if($invitation->role && $invitation->role->description)
                                                    {{ $invitation->role->description }}
                                                @else
                                                    Standardzugang zum Kundenportal
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Spacer -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-top: 28px;"></td>
                                    </tr>
                                </table>

                                <!-- Benefits Section -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td>
                                            <p style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Mit Ihrem Zugang können Sie:
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                @php
                                    $roleName = $invitation->role->name ?? 'operator';
                                @endphp

                                <!-- Benefits List -->
                                @if($roleName === 'viewer')
                                    <!-- Viewer Benefits -->
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6; position: relative;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Ihre Termine jederzeit online einsehen
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Termindetails und -historie ansehen
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Übersicht über vergangene und zukünftige Termine
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Viewer Hint -->
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="padding: 12px 16px; background-color: #fef9c3; border: 1px solid #fde047; border-radius: 6px; margin-top: 16px;">
                                                <p style="margin: 0; font-size: 13px; color: #854d0e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                    ℹ️ Als "Viewer" können Sie Termine ansehen, aber nicht ändern oder buchen.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                @elseif($roleName === 'manager')
                                    <!-- Manager Benefits -->
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Vollständige Terminverwaltung
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Termine ansehen, buchen, verschieben und stornieren
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Erweiterte Terminübersicht und Auswertungen
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Zugriff auf alle verfügbaren Funktionen
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Management-Dashboard mit Statistiken
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <!-- Operator Benefits (Default) -->
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Ihre Termine jederzeit online einsehen
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Neue Termine bequem online buchen
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Termine flexibel verschieben
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px; border-bottom: 1px solid #f3f4f6;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Termine bei Bedarf stornieren
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 0 12px 36px;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="24" valign="top" style="padding-right: 12px;">
                                                            <span style="display: inline-block; width: 24px; height: 24px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 14px;">✓</span>
                                                        </td>
                                                        <td style="color: #4b5563; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            Alternative Terminvorschläge erhalten
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                @endif

                                <!-- Spacer -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-top: 36px;"></td>
                                    </tr>
                                </table>

                                <!-- CTA Button -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td align="center" style="padding: 0;">
                                            <!--[if mso]>
                                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ url('/kundenportal/einladung/' . $invitation->token) }}" style="height:52px;v-text-anchor:middle;width:250px;" arcsize="19%" strokecolor="#2563eb" fillcolor="#2563eb">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:16px;font-weight:600;">Jetzt Konto erstellen →</center>
                                            </v:roundrect>
                                            <![endif]-->
                                            <!--[if !mso]><!-->
                                            <a href="{{ url('/kundenportal/einladung/' . $invitation->token) }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 10px; font-weight: 600; font-size: 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; text-align: center;">
                                                Jetzt Konto erstellen →
                                            </a>
                                            <!--<![endif]-->
                                        </td>
                                    </tr>
                                </table>

                                <!-- Spacer -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-top: 36px;"></td>
                                    </tr>
                                </table>

                                <!-- Expiry Notice -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 16px 20px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; text-align: center;">
                                            <p style="margin: 0 0 8px 0; font-size: 20px; line-height: 1;">⏰</p>
                                            <p style="margin: 0; color: #991b1b; font-size: 14px; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Diese Einladung ist <strong style="color: #7f1d1d;">72 Stunden</strong> gültig und läuft am
                                                <strong style="color: #7f1d1d;">{{ $invitation->expires_at->format('d.m.Y') }} um {{ $invitation->expires_at->format('H:i') }} Uhr</strong> ab.
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Spacer -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-top: 24px;"></td>
                                    </tr>
                                </table>

                                <!-- Link Fallback -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 16px; background-color: #f9fafb; border-radius: 8px;">
                                            <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Falls der Button nicht funktioniert, kopieren Sie diesen Link:
                                            </p>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 10px; background-color: #ffffff; border-radius: 4px; border: 1px solid #e5e7eb;">
                                                        <p style="margin: 0; word-break: break-all; color: #2563eb; font-size: 12px; font-family: Monaco, Consolas, monospace; line-height: 1.5;">
                                                            {{ url('/kundenportal/einladung/' . $invitation->token) }}
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding: 32px 40px; background-color: #f9fafb; text-align: center; border-top: 1px solid #e5e7eb;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td align="center">
                                            <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Diese E-Mail wurde automatisch von {{ config('app.name', 'AskPro') }} versendet.
                                            </p>
                                            <p style="margin: 0 0 16px 0; font-size: 13px; color: #6b7280; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                Bei Fragen kontaktieren Sie uns:
                                                <a href="mailto:{{ $invitation->company->email ?? config('mail.from.address') }}" style="color: #2563eb; text-decoration: none;">
                                                    {{ $invitation->company->email ?? config('mail.from.address') }}
                                                </a>
                                            </p>
                                            <!-- Separator -->
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-top: 16px; border-top: 1px solid #e5e7eb;">
                                                <tr>
                                                    <td align="center">
                                                        <p style="margin: 0; font-size: 11px; color: #9ca3af; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                                            © {{ date('Y') }} {{ $invitation->company->name ?? config('app.name', 'AskPro') }}. Alle Rechte vorbehalten.
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
                    <!-- End Email Container -->
                </td>
            </tr>
        </table>
        <!-- End Outer Wrapper Table -->

        <!--[if mso | IE]>
        </td>
        </tr>
        </table>
        <![endif]-->
    </center>
</body>
</html>
