<?php

/**
 * Fix für Call-Anzeige mit korrekter Timezone
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Jobs\FetchRetellCallsJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

echo "\n=== Call Display & Timezone Fix ===\n\n";

// 1. Zeige aktuelle Timezone-Einstellungen
echo "1. Timezone-Einstellungen:\n";
echo "   App Timezone: " . config('app.timezone') . "\n";
echo "   Current Time (App): " . Carbon::now()->format('Y-m-d H:i:s T') . "\n";
echo "   Current Date (App): " . Carbon::now()->toDateString() . "\n";

// 2. Prüfe Datenbank-Timezone
$dbTimezone = \DB::select("SELECT @@time_zone as tz")[0]->tz;
echo "   DB Timezone: " . $dbTimezone . "\n";
$dbTime = \DB::select("SELECT NOW() as now, CURDATE() as today")[0];
echo "   DB Current Time: " . $dbTime->now . "\n";
echo "   DB Current Date: " . $dbTime->today . "\n\n";

// 3. Zeige Calls von heute (verschiedene Methoden)
echo "2. Calls von heute (verschiedene Abfragen):\n";

// Setze Company Context für Tenant Scope
$company = Company::first();
if ($company) {
    // Login als admin user für Company Context
    $user = \App\Models\User::where('email', 'admin@askproai.com')->first();
    if ($user) {
        Auth::login($user);
        echo "   Logged in as: {$user->email}\n";
    }
    echo "   Company Context: {$company->name} (ID: {$company->id})\n\n";
}

// Methode 1: Mit Carbon (App Timezone) - ohne TenantScope
$todayStart = Carbon::today('Europe/Berlin');
$todayEnd = Carbon::tomorrow('Europe/Berlin');
$callsCarbon = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->whereBetween('start_timestamp', [$todayStart, $todayEnd])
    ->count();
echo "   Carbon (start_timestamp): $callsCarbon Calls\n";

// Methode 2: Mit created_at
$callsCreated = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->whereDate('created_at', Carbon::today())
    ->count();
echo "   Carbon (created_at): $callsCreated Calls\n";

// Methode 3: Raw SQL wie im Tab
$callsRaw = \DB::select("SELECT COUNT(*) as count FROM calls WHERE company_id = ? AND DATE(start_timestamp) = CURDATE()", [$company->id])[0]->count;
echo "   Raw SQL (DATE=CURDATE): $callsRaw Calls\n";

// Methode 4: Mit explizitem Datum
$todayDateString = Carbon::now('Europe/Berlin')->toDateString();
$callsExplicit = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->whereDate('start_timestamp', $todayDateString)
    ->count();
echo "   Explicit Date ($todayDateString): $callsExplicit Calls\n\n";

// 4. Zeige letzte 5 Calls
echo "3. Letzte 5 Calls:\n";
$recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->orderBy('start_timestamp', 'desc')
    ->limit(5)
    ->get();
foreach ($recentCalls as $call) {
    echo "   - {$call->call_id}: " . 
         ($call->start_timestamp ? $call->start_timestamp->format('Y-m-d H:i:s') : 'NULL') . 
         " (created: " . $call->created_at->format('Y-m-d H:i:s') . ")\n";
}

// 5. Calls der letzten 24 Stunden
$calls24h = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->where('start_timestamp', '>', Carbon::now()->subHours(24))
    ->count();
echo "\n4. Calls in den letzten 24 Stunden: $calls24h\n";

// 6. Trigger manuellen Call-Import
echo "\n5. Triggere manuellen Call-Import...\n";
if ($company) {
    // Dispatch Job
    FetchRetellCallsJob::dispatch($company)->onQueue('high');
    echo "   ✅ Import-Job wurde gestartet\n";
    echo "   ⏳ Warte 5 Sekunden...\n";
    sleep(5);
    
    // Prüfe neue Calls
    $newCallsCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('created_at', '>', Carbon::now()->subMinutes(1))
        ->count();
    echo "   📞 Neue Calls in der letzten Minute: $newCallsCount\n";
} else {
    echo "   ❌ Keine Company gefunden\n";
}

// 7. Empfohlene Fixes
echo "\n6. Empfohlene Lösungen:\n";
echo "   1. Stelle sicher dass Horizon läuft: php artisan horizon\n";
echo "   2. Nutze den 'Anrufe abrufen' Button im Admin Panel\n";
echo "   3. Prüfe die Filter-Einstellungen (oben rechts)\n";

// 8. Quick Fix für Timezone
if ($dbTimezone !== '+01:00' && $dbTimezone !== 'Europe/Berlin') {
    echo "\n⚠️  WARNUNG: Datenbank nutzt andere Timezone als App!\n";
    echo "   Empfehlung: SET time_zone = '+01:00' in MySQL\n";
}

echo "\n=== Fix abgeschlossen ===\n";