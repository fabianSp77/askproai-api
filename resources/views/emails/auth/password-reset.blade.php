<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="format-detection" content="telephone=no" />
    <title>Passwort zuruecksetzen | Password Reset</title>
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
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: auto !important;
            }
            .mobile-padding {
                padding: 24px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <center style="width: 100%; background-color: #f3f4f6;">

        <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
            Setzen Sie Ihr Passwort zurueck - AskProAI Gateway
        </div>

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; padding: 0; background-color: #f3f4f6;">
            <tr>
                <td style="padding: 40px 20px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; max-width: 600px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">

                        <tr>
                            <td style="background-color: #f59e0b; background-image: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); padding: 40px; text-align: center;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                    <tr>
                                        <td style="background-color: rgba(255,255,255,0.2); border-radius: 50%; width: 80px; height: 80px; text-align: center; vertical-align: middle; font-size: 36px;">
                                            &#128274;
                                        </td>
                                    </tr>
                                </table>
                                <h1 style="margin: 24px 0 0 0; font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">
                                    AskProAI Gateway
                                </h1>
                            </td>
                        </tr>

                        <tr>
                            <td class="mobile-padding" style="padding: 40px 48px 32px 48px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #fef3c7; border-radius: 20px; padding: 6px 14px;">
                                            <span style="font-size: 13px; font-weight: 600; color: #92400e;">&#127465;&#127466; DEUTSCH</span>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin: 0 0 20px 0; font-size: 18px; color: #1f2937;">
                                    Hallo <strong>{{ $user->name ?? 'Nutzer' }}</strong>,
                                </p>

                                <p style="margin: 0 0 24px 0; font-size: 16px; color: #4b5563; line-height: 1.7;">
                                    Sie haben eine Anfrage zum Zuruecksetzen Ihres Passworts fuer Ihr AskProAI Konto gestellt.
                                    Klicken Sie auf den Button unten, um ein neues Passwort zu erstellen.
                                </p>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto 24px auto;">
                                    <tr>
                                        <td style="border-radius: 12px; background-color: #f59e0b; background-image: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); box-shadow: 0 4px 14px 0 rgba(245, 158, 11, 0.4);">
                                            <a href="{{ $url }}" target="_blank" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 12px;">
                                                Passwort zuruecksetzen
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; padding: 16px 20px;">
                                            <p style="margin: 0; font-size: 14px; color: #92400e;">
                                                <strong>&#9201; Wichtig:</strong> Dieser Link ist <strong>{{ $expireMinutes }} Minuten</strong> gueltig.
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                    <strong>&#128274; Sicherheitshinweis:</strong> Falls Sie diese Anfrage nicht gestellt haben,
                                    koennen Sie diese E-Mail ignorieren. Ihr Passwort bleibt unveraendert.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 0 48px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="border-top: 2px dashed #e5e7eb; padding-top: 8px;">
                                            <p style="margin: 0; font-size: 12px; color: #9ca3af; text-align: center;">
                                                &#8595; English version below &#8595;
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td class="mobile-padding" style="padding: 32px 48px 40px 48px; background-color: #fafafa;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #dbeafe; border-radius: 20px; padding: 6px 14px;">
                                            <span style="font-size: 13px; font-weight: 600; color: #1e40af;">&#127468;&#127463; ENGLISH</span>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin: 0 0 20px 0; font-size: 18px; color: #1f2937;">
                                    Hello <strong>{{ $user->name ?? 'User' }}</strong>,
                                </p>

                                <p style="margin: 0 0 24px 0; font-size: 16px; color: #4b5563; line-height: 1.7;">
                                    You requested a password reset for your AskProAI account.
                                    Click the button below to create a new password.
                                </p>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto 24px auto;">
                                    <tr>
                                        <td style="border-radius: 12px; background-color: #3b82f6; box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4);">
                                            <a href="{{ $url }}" target="_blank" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 12px;">
                                                Reset Password
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 0 8px 8px 0; padding: 16px 20px;">
                                            <p style="margin: 0; font-size: 14px; color: #1e40af;">
                                                <strong>&#9201; Important:</strong> This link expires in <strong>{{ $expireMinutes }} minutes</strong>.
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                    <strong>&#128274; Security Notice:</strong> If you did not request this password reset,
                                    you can safely ignore this email. Your password will remain unchanged.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="background-color: #1f2937; padding: 32px 48px; text-align: center;">
                                <p style="margin: 0 0 8px 0; font-size: 14px; color: #9ca3af;">
                                    AskProAI Gateway
                                </p>
                                <p style="margin: 0 0 16px 0; font-size: 12px; color: #6b7280;">
                                    Intelligente Terminverwaltung mit KI-Sprachassistenz
                                </p>
                                <p style="margin: 0; font-size: 11px; color: #4b5563;">
                                    &copy; {{ date('Y') }} AskProAI. Alle Rechte vorbehalten.
                                </p>
                            </td>
                        </tr>

                    </table>

                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="margin: 24px auto 0 auto; max-width: 600px;">
                        <tr>
                            <td style="text-align: center; padding: 0 20px;">
                                <p style="margin: 0; font-size: 11px; color: #9ca3af; line-height: 1.6;">
                                    Falls der Button nicht funktioniert, kopieren Sie diesen Link:<br>
                                    <span style="color: #6b7280; word-break: break-all;">{{ $url }}</span>
                                </p>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

    </center>
</body>
</html>
