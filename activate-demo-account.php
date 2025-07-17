<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

// Find the user
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("❌ Benutzer mit E-Mail fabianspitzer@icloud.com nicht gefunden!\n");
}

echo "✅ Benutzer gefunden: {$user->name}\n";
echo "   Firma: {$user->company->name}\n";
echo "   Status: " . ($user->is_active ? 'Aktiv' : 'Inaktiv') . "\n\n";

// Activate the user
$user->is_active = true;
$user->role = 'admin'; // Ensure admin role for demo
$user->save();

// Update company to mark as demo
$company = $user->company;
$settings = $company->settings ?? [];
$settings['is_demo_account'] = true;
$settings['demo_features'] = [
    'show_sample_data' => true,
    'allow_data_reset' => true,
    'highlight_demo_features' => true
];
$company->settings = $settings;
$company->save();

// Add demo data marker to user
$user->permissions = json_encode([
    '*', // All permissions
    'demo_account' => true
]);
$user->save();

// Create some demo data
echo "🎯 Richte Demo-Account ein...\n";

// Add demo marker to registration data
$registrationData = $user->registration_data ?? [];
$registrationData['demo_account'] = true;
$registrationData['demo_note'] = 'DEMO ACCOUNT - Für Produktdemos und Feature-Tests. Daten können jederzeit zurückgesetzt werden.';
$user->registration_data = $registrationData;
$user->save();

echo "\n✅ Demo-Account erfolgreich eingerichtet!\n";
echo "=====================================\n";
echo "Email: fabianspitzer@icloud.com\n";
echo "Rolle: Admin (Demo)\n";
echo "Firma: {$company->name}\n";
echo "Status: Aktiv\n";
echo "\n🎯 Demo-Features:\n";
echo "- Alle Berechtigungen freigeschaltet\n";
echo "- Sample-Daten verfügbar\n";
echo "- Daten können zurückgesetzt werden\n";
echo "- Demo-Markierungen in der UI\n";
echo "\n📝 Hinweis: Der Benutzer sollte eine Aktivierungs-E-Mail erhalten haben.\n";

// Send activation email
try {
    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PortalUserActivated($user));
    echo "✉️  Aktivierungs-E-Mail wurde gesendet.\n";
} catch (\Exception $e) {
    echo "⚠️  Aktivierungs-E-Mail konnte nicht gesendet werden: " . $e->getMessage() . "\n";
}