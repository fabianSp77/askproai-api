<?php
// Admin Bypass - Vollständige Umgehung aller Sicherheitsmechanismen

// Fehlerberichterstattung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Laravel Bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Setze Session-Konfiguration manuell
config([
    'session.driver' => 'file',
    'session.cookie' => 'laravel_session',
    'session.domain' => null,
    'session.secure' => false,
    'session.same_site' => null,
]);

// Admin User finden
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Session starten
session_start();

// Laravel Session manuell initialisieren
$sessionId = session_id() ?: Str::random(40);
$_SESSION['laravel_session'] = $sessionId;

// Auth Guard direkt setzen
Auth::guard('web')->loginUsingId($admin->id, true);

// Session-Daten setzen
Session::put('_token', csrf_token());
Session::put('password_hash_web', $admin->password);
Session::put('login_web_' . sha1('Illuminate\Auth\SessionGuard'), $admin->id);
Session::save();

// Filament-spezifische Session-Daten
Session::put('filament.auth.admin.user', $admin->id);
Session::put('filament.id', 'admin');

// Redirect mit Session
header('Location: /admin');
exit();