<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\CallActivity;
use Illuminate\Support\Facades\DB;

// Get a recent call without tenant scope
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    echo "No calls found in database.\n";
    exit(1);
}

echo "Adding activities for call ID: {$call->id}\n";

// Create some sample activities
$activities = [
    [
        'call_id' => $call->id,
        'company_id' => $call->company_id,
        'activity_type' => CallActivity::TYPE_CALL_RECEIVED,
        'title' => 'Anruf eingegangen',
        'description' => 'Anruf von ' . $call->from_number . ' eingegangen',
        'icon' => 'Phone',
        'color' => 'blue',
        'is_system' => true,
        'created_at' => $call->created_at,
    ],
    [
        'call_id' => $call->id,
        'company_id' => $call->company_id,
        'activity_type' => CallActivity::TYPE_ANALYZED,
        'title' => 'Anruf analysiert',
        'description' => 'KI-Analyse abgeschlossen',
        'icon' => 'CheckCircle',
        'color' => 'green',
        'is_system' => true,
        'created_at' => $call->created_at->addMinutes(1),
    ],
    [
        'call_id' => $call->id,
        'company_id' => $call->company_id,
        'activity_type' => CallActivity::TYPE_SUMMARY_GENERATED,
        'title' => 'Zusammenfassung erstellt',
        'description' => 'Automatische Zusammenfassung generiert',
        'icon' => 'FileText',
        'color' => 'green',
        'is_system' => true,
        'created_at' => $call->created_at->addMinutes(2),
    ],
];

DB::beginTransaction();
try {
    foreach ($activities as $activity) {
        CallActivity::create($activity);
        echo "Created activity: {$activity['title']}\n";
    }
    
    DB::commit();
    echo "\nSuccessfully created " . count($activities) . " activities for call.\n";
} catch (\Exception $e) {
    DB::rollback();
    echo "Error creating activities: " . $e->getMessage() . "\n";
}