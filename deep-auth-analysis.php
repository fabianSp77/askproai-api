<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "\n🔍 TIEFGREIFENDE AUTHENTIFIZIERUNGS-ANALYSE\n";
echo "==========================================\n\n";

// 1. Check User Data
echo "1️⃣ BENUTZER-DATEN PRÜFUNG:\n";
echo "----------------------------\n";
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("❌ KRITISCH: Benutzer nicht in Datenbank gefunden!\n");
}

echo "✅ Benutzer gefunden:\n";
echo "   ID: {$user->id}\n";
echo "   Email: {$user->email}\n";
echo "   Name: {$user->name}\n";
echo "   Aktiv: " . ($user->is_active ? 'JA' : 'NEIN') . "\n";
echo "   Firma ID: {$user->company_id}\n";
echo "   Rolle: {$user->role}\n";
echo "   Passwort-Hash: " . substr($user->password, 0, 20) . "...\n";
echo "   Hash-Länge: " . strlen($user->password) . " Zeichen\n";

// Check if password field is properly filled
if (empty($user->password)) {
    echo "❌ KRITISCH: Passwort-Feld ist leer!\n";
} elseif (strlen($user->password) < 50) {
    echo "⚠️ WARNUNG: Passwort-Hash zu kurz (sollte 60 Zeichen für bcrypt sein)\n";
}

// 2. Password Hash Test
echo "\n2️⃣ PASSWORT-HASH TEST:\n";
echo "------------------------\n";
$testPasswords = ['test123', 'Test123', 'password', 'demo123', 'Demo123'];
$hashValid = false;

foreach ($testPasswords as $testPw) {
    if (Hash::check($testPw, $user->password)) {
        echo "✅ Passwort gefunden: '{$testPw}'\n";
        $hashValid = true;
        break;
    }
}

if (!$hashValid) {
    echo "❌ Keines der Test-Passwörter passt zum Hash\n";
    
    // Try to create a new hash
    $newHash = Hash::make('test123');
    echo "   Neuer Hash für 'test123': " . substr($newHash, 0, 20) . "...\n";
    echo "   Vergleich mit DB-Hash: " . (Hash::check('test123', $newHash) ? 'VALID' : 'INVALID') . "\n";
}

// 3. Auth Guard Configuration
echo "\n3️⃣ AUTH GUARD KONFIGURATION:\n";
echo "------------------------------\n";
$guards = config('auth.guards');
echo "Verfügbare Guards:\n";
foreach ($guards as $name => $config) {
    echo "   - {$name}: Driver=" . ($config['driver'] ?? 'N/A') . ", Provider=" . ($config['provider'] ?? 'N/A') . "\n";
}

// Check portal guard specifically
$portalGuard = config('auth.guards.portal');
echo "\nPortal Guard Details:\n";
echo "   Driver: " . ($portalGuard['driver'] ?? 'N/A') . "\n";
echo "   Provider: " . ($portalGuard['provider'] ?? 'N/A') . "\n";

// Check provider
$portalProvider = config('auth.providers.' . ($portalGuard['provider'] ?? ''));
echo "\nPortal Provider Details:\n";
echo "   Driver: " . ($portalProvider['driver'] ?? 'N/A') . "\n";
echo "   Model: " . ($portalProvider['model'] ?? 'N/A') . "\n";

// 4. Direct Authentication Test
echo "\n4️⃣ DIREKTE AUTHENTIFIZIERUNGS-TESTS:\n";
echo "--------------------------------------\n";

// Test manual authentication
$guard = Auth::guard('portal');
echo "Auth Guard Type: " . get_class($guard) . "\n";

// Try manual login
$credentials = [
    'email' => 'fabianspitzer@icloud.com',
    'password' => 'test123'
];

echo "\nTest 1: Attempt mit Credentials:\n";
$attempt = $guard->attempt($credentials);
echo "   Ergebnis: " . ($attempt ? 'ERFOLG' : 'FEHLGESCHLAGEN') . "\n";

if (!$attempt) {
    // Get last auth error
    echo "   Session Errors: " . json_encode(session()->get('errors')) . "\n";
}

// Test 2: Direct login
echo "\nTest 2: Direct Login:\n";
try {
    $guard->login($user);
    echo "   Direct Login: ERFOLG\n";
    echo "   Authenticated: " . ($guard->check() ? 'JA' : 'NEIN') . "\n";
    echo "   User ID: " . ($guard->id() ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "   Direct Login: FEHLER - " . $e->getMessage() . "\n";
}

// 5. Database Query Test
echo "\n5️⃣ DATENBANK-QUERY TEST:\n";
echo "--------------------------\n";
$rawUser = DB::table('portal_users')
    ->where('email', 'fabianspitzer@icloud.com')
    ->first();

echo "Raw DB Query Result:\n";
echo "   ID: " . ($rawUser->id ?? 'N/A') . "\n";
echo "   Email: " . ($rawUser->email ?? 'N/A') . "\n";
echo "   Password Length: " . strlen($rawUser->password ?? '') . "\n";

// 6. Session Test
echo "\n6️⃣ SESSION TEST:\n";
echo "-----------------\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Started: " . (session()->isStarted() ? 'JA' : 'NEIN') . "\n";
echo "Session ID: " . session()->getId() . "\n";
echo "Session Domain: " . config('session.domain') . "\n";
echo "Session Path: " . config('session.path') . "\n";

// 7. Middleware Analysis
echo "\n7️⃣ MIDDLEWARE ANALYSE:\n";
echo "----------------------\n";
$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
echo "Kernel Type: " . get_class($kernel) . "\n";

// 8. Login Controller Check
echo "\n8️⃣ LOGIN CONTROLLER CHECK:\n";
echo "---------------------------\n";
$loginController = 'App\Http\Controllers\Portal\Auth\LoginController';
if (class_exists($loginController)) {
    echo "✅ LoginController existiert\n";
    $reflection = new ReflectionClass($loginController);
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    echo "Public Methods:\n";
    foreach ($methods as $method) {
        if ($method->class == $loginController) {
            echo "   - " . $method->name . "\n";
        }
    }
} else {
    echo "❌ LoginController nicht gefunden!\n";
}

// 9. Final Password Reset Test
echo "\n9️⃣ PASSWORT NEU SETZEN:\n";
echo "------------------------\n";
$user->password = Hash::make('test123');
$user->save();
echo "✅ Passwort neu gesetzt auf 'test123'\n";
echo "   Neuer Hash: " . substr($user->password, 0, 20) . "...\n";

// Test again
$finalAttempt = Auth::guard('portal')->attempt([
    'email' => 'fabianspitzer@icloud.com',
    'password' => 'test123'
]);
echo "   Login-Test: " . ($finalAttempt ? 'ERFOLG!' : 'IMMER NOCH FEHLGESCHLAGEN') . "\n";

echo "\n🔍 ZUSAMMENFASSUNG:\n";
echo "==================\n";
if ($finalAttempt) {
    echo "✅ Login funktioniert nach Passwort-Reset!\n";
    echo "   Email: fabianspitzer@icloud.com\n";
    echo "   Passwort: test123\n";
} else {
    echo "❌ Login funktioniert NICHT - tieferes Problem!\n";
    echo "   Mögliche Ursachen:\n";
    echo "   - Auth Guard falsch konfiguriert\n";
    echo "   - Session-Problem\n";
    echo "   - Middleware blockiert\n";
    echo "   - Model-Problem\n";
}