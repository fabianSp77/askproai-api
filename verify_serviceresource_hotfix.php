<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   SERVICERESOURCE HOTFIX VERIFICATION               ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Check 1: Verify no ->description() on TextEntry in ViewService.php
echo "1️⃣ CHECKING FOR TEXTENTRY->DESCRIPTION() ERRORS:\n";

$viewServiceFile = file_get_contents(__DIR__.'/app/Filament/Resources/ServiceResource/Pages/ViewService.php');

// Look for TextEntry with ->description (should be 0)
preg_match_all('/TextEntry::make.*?->description\(/s', $viewServiceFile, $matches);

if (count($matches[0]) === 0) {
    echo "   ✅ No TextEntry->description() found\n";
} else {
    echo "   ❌ Found " . count($matches[0]) . " TextEntry->description() calls\n";
    foreach ($matches[0] as $match) {
        echo "      " . substr($match, 0, 80) . "...\n";
    }
}

// Check 2: Verify ->helperText() is used instead
preg_match_all('/TextEntry::make.*?->helperText\(/s', $viewServiceFile, $helperMatches);
echo "   ℹ️  Found " . count($helperMatches[0]) . " TextEntry->helperText() calls (expected: 2+)\n";

echo "\n";

// Check 3: Test sample services
echo "2️⃣ TESTING SAMPLE SERVICES:\n";

$testServices = [
    32 => 'AskProAI - 15 Minuten Schnellberatung',
    170 => 'Friseur 1 - Damenha... (reported error)',
    167 => 'Friseur 1 - First service',
];

foreach ($testServices as $serviceId => $description) {
    $service = App\Models\Service::find($serviceId);

    if (!$service) {
        echo "   ⚠️  Service $serviceId: NOT FOUND\n";
        continue;
    }

    echo "   Service $serviceId ($description):\n";
    echo "      Company: {$service->company->name}\n";
    echo "      Team ID: " . ($service->company->calcom_team_id ?? 'NULL') . "\n";
    echo "      Event Type ID: " . ($service->calcom_event_type_id ?? 'NULL') . "\n";
    echo "      Last Sync: " . ($service->last_calcom_sync ? $service->last_calcom_sync->format('Y-m-d H:i') : 'Never') . "\n";

    // Check mapping
    if ($service->calcom_event_type_id) {
        $mapping = DB::table('calcom_event_mappings')
            ->where('calcom_event_type_id', $service->calcom_event_type_id)
            ->first();

        if ($mapping) {
            $teamMatch = $mapping->calcom_team_id == $service->company->calcom_team_id;
            echo "      Mapping: " . ($teamMatch ? '✅ Team ID matches' : '⚠️ Team ID MISMATCH') . "\n";
        } else {
            echo "      Mapping: ❌ NOT FOUND\n";
        }
    } else {
        echo "      Mapping: ○ No Event Type ID\n";
    }

    echo "\n";
}

echo "3️⃣ CACHE STATUS:\n";

// Check if view cache is clear
$viewCachePath = storage_path('framework/views');
$cachedViews = glob($viewCachePath . '/*.php');
echo "   Cached views: " . count($cachedViews) . " files\n";

if (file_exists(base_path('bootstrap/cache/config.php'))) {
    echo "   ⚠️  Config cached (run: php artisan config:clear)\n";
} else {
    echo "   ✅ Config not cached\n";
}

echo "\n";

echo "4️⃣ RECOMMENDED MANUAL TESTS:\n";
echo "   1. Visit: https://api.askproai.de/admin/services/170\n";
echo "      Expected: Page loads, Cal.com section expanded\n";
echo "      Expected: See 'Multi-Tenant Isolation' helper text below Team ID\n";
echo "      Expected: See relative time below 'Letzter Sync'\n";
echo "\n";
echo "   2. Visit: https://api.askproai.de/admin/services/32\n";
echo "      Expected: Page loads without errors\n";
echo "      Expected: Team ID: 39203 (AskProAI)\n";
echo "\n";
echo "   3. Visit: https://api.askproai.de/admin/services/167\n";
echo "      Expected: Page loads without errors\n";
echo "      Expected: Team ID: 34209 (Friseur 1)\n";

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║                 VERIFICATION COMPLETE                 ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
