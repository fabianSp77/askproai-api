<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Neue Portal Registrierung</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #4F46E5;">Neue Business Portal Registrierung</h2>
        
        <p>Eine neue Registrierung wurde im Business Portal eingereicht und wartet auf Freischaltung.</p>
        
        <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Firmendaten:</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Firma:</strong></td>
                    <td>{{ $company->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Telefon:</strong></td>
                    <td>{{ $company->phone }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>E-Mail:</strong></td>
                    <td>{{ $company->email }}</td>
                </tr>
                @if($company->address)
                <tr>
                    <td style="padding: 5px 0;"><strong>Adresse:</strong></td>
                    <td>{{ $company->address }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Benutzerdaten:</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Name:</strong></td>
                    <td>{{ $user->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>E-Mail:</strong></td>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Telefon:</strong></td>
                    <td>{{ $user->phone }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Registriert am:</strong></td>
                    <td>{{ $user->created_at->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            </table>
        </div>
        
        <div style="margin: 30px 0;">
            <p><strong>Aktion erforderlich:</strong></p>
            <p>Bitte prüfen Sie die Registrierung und schalten Sie den Account im Admin-Panel frei:</p>
            <a href="{{ url('/admin/portal-users/' . $user->id . '/edit') }}" 
               style="display: inline-block; background-color: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Zur Freischaltung im Admin-Panel
            </a>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <p style="font-size: 12px; color: #6b7280;">
            Diese E-Mail wurde automatisch vom AskProAI System generiert.
        </p>
    </div>
</body>
</html>