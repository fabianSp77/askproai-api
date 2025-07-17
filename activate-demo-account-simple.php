<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;

// Find the user
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("❌ Benutzer mit E-Mail fabianspitzer@icloud.com nicht gefunden!\n");
}

echo "✅ Benutzer gefunden: {$user->name}\n";
echo "   Firma: {$user->company->name}\n";
echo "   Firma ID: {$user->company_id}\n";
echo "   Aktueller Status: " . ($user->is_active ? 'Aktiv' : 'Inaktiv') . "\n";
echo "   Aktuelle Rolle: {$user->role}\n\n";

// Activate the user and set as demo
$user->is_active = true;
$user->role = 'admin';
$user->permissions = json_encode([
    'calls.*',
    'appointments.*',
    'customers.*',
    'billing.view',
    'analytics.*',
    'settings.*',
    'team.*',
    'demo_account' => true
]);
$user->save();

// Update company settings for demo
$company = $user->company;
$currentSettings = is_string($company->settings) ? json_decode($company->settings, true) : ($company->settings ?? []);
$currentSettings['is_demo_account'] = true;
$currentSettings['demo_label'] = 'DEMO Account - Für Vorführungen und Tests';
$company->settings = json_encode($currentSettings);
$company->save();

echo "✅ Demo-Account erfolgreich aktiviert!\n";
echo "=====================================\n";
echo "Email: fabianspitzer@icloud.com\n";
echo "Status: Aktiv ✓\n";
echo "Rolle: Admin ✓\n";
echo "Firma: {$company->name} (Demo-Markierung hinzugefügt)\n";
echo "\n🎯 Demo-Features aktiviert:\n";
echo "- Alle Module freigeschaltet\n";
echo "- Demo-Markierung in der Firma\n";
echo "- Volle Admin-Rechte\n";
echo "\n📝 Der Benutzer kann sich jetzt einloggen unter:\n";
echo "URL: https://api.askproai.de/business/login\n";

// Try to send activation email
try {
    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PortalUserActivated($user));
    echo "\n✉️ Aktivierungs-E-Mail wurde gesendet an fabianspitzer@icloud.com\n";
} catch (\Exception $e) {
    echo "\n⚠️ E-Mail konnte nicht gesendet werden (ist aber nicht kritisch)\n";
}

echo "\n🚀 Demo-Account ist bereit für Vorführungen!\n";