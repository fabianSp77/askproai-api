#!/usr/bin/env php
<?php

/**
 * Implementation Guide: Dynamic Date Injection for Retell Agent
 *
 * This script shows how to implement dynamic date injection
 * so the agent always has access to current date/time
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Dynamic Date Injection - Implementation Guide\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Demo: How to generate dynamic date context
$now = Carbon::now('Europe/Berlin');

$dynamicContext = [
    'current_date' => $now->format('Y-m-d'),
    'current_time' => $now->format('H:i'),
    'day_of_week' => $now->locale('de')->dayName,
    'day_number' => $now->day,
    'month_name' => $now->locale('de')->monthName,
    'month_number' => $now->month,
    'year' => $now->year,
    'week_number' => $now->weekOfYear,
    'is_weekend' => $now->isWeekend(),
    'tomorrow_date' => $now->copy()->addDay()->format('Y-m-d'),
    'tomorrow_day' => $now->copy()->addDay()->locale('de')->dayName,
];

echo "🕐 Generated Dynamic Context:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

foreach ($dynamicContext as $key => $value) {
    $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
    echo sprintf("%-20s → %s\n", $key, $displayValue);
}

echo "\n";
echo "📝 Implementation Steps:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "STEP 1: Update RetellWebhookController.php\n";
echo "---------------------------------------------------\n";
echo <<<'PHP'
// app/Http/Controllers/RetellWebhookController.php

public function handleInboundCall(Request $request)
{
    $now = Carbon::now('Europe/Berlin');

    $dynamicVariables = [
        'current_date' => $now->format('Y-m-d'),
        'current_time' => $now->format('H:i'),
        'day_of_week' => $now->locale('de')->dayName,
        'month_name' => $now->locale('de')->monthName,
        'year' => $now->year,
        'tomorrow_date' => $now->copy()->addDay()->format('Y-m-d'),
    ];

    // Return these in response
    return response()->json([
        'agent_id' => config('services.retellai.agent_id'),
        'dynamic_variables' => $dynamicVariables,
    ]);
}
PHP;

echo "\n\n";

echo "STEP 2: Add get_current_context Function (Optional)\n";
echo "---------------------------------------------------\n";
echo <<<'PHP'
// app/Http/Controllers/RetellFunctionCallHandler.php

public function getCurrentContext(Request $request): JsonResponse
{
    $now = Carbon::now('Europe/Berlin');

    return response()->json([
        'date' => $now->format('Y-m-d'),
        'time' => $now->format('H:i'),
        'day' => $now->locale('de')->dayName,
        'month' => $now->locale('de')->monthName,
        'year' => $now->year,
        'timezone' => 'Europe/Berlin',
        'formatted' => $now->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
    ]);
}
PHP;

echo "\n\n";

echo "STEP 3: Register Function in Retell Agent\n";
echo "---------------------------------------------------\n";
echo <<<'JSON'
{
  "name": "get_current_context",
  "description": "Ruft aktuelles Datum und Uhrzeit ab",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "parameters": {
    "type": "object",
    "properties": {
      "call_id": {
        "type": "string",
        "description": "Die Call ID"
      }
    },
    "required": ["call_id"]
  }
}
JSON;

echo "\n\n";

echo "STEP 4: Update Global Prompt to use {{variables}}\n";
echo "---------------------------------------------------\n";
echo <<<'MARKDOWN'
Verwende in V48 Prompt:

{{current_date}} - Aktuelles Datum (z.B. "2025-11-06")
{{current_time}} - Aktuelle Uhrzeit (z.B. "14:30")
{{day_of_week}} - Wochentag (z.B. "Mittwoch")

Beispiel im Prompt:
"Heute ist {{day_of_week}}, der {{current_date}}."
MARKDOWN;

echo "\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo " ✅ Implementation Guide Complete\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Next Steps:\n";
echo "1. Implement backend changes in RetellWebhookController\n";
echo "2. Add get_current_context function (optional)\n";
echo "3. Register function in Retell Dashboard\n";
echo "4. Update Global Prompt to V48\n";
echo "5. Test with: php scripts/test_dynamic_date.php\n";
echo "\n";
