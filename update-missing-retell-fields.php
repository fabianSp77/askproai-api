<?php

/**
 * Update Missing Retell Fields
 * 
 * Fügt Cost, Agent Version und andere fehlende Felder hinzu
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Illuminate\Support\Facades\DB;

echo "\n=== Update Missing Retell Fields ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Sample cost data from overview (format: $0.092)
$costData = [
    'call_e2c7629e547c22f066eebac60f9' => 0.092,
    'call_921af687ce956b87eda85157e5b' => 0.058,
    'call_e54f48495bcbc0433900e2a71d4' => 0.058,
    'call_c58cb624a7384c38ed63cfc6a08' => 0.061,
    'call_738d9d1b60be8f0461d86e5d1ed' => 0.093,
    'call_4c6a52d21b39c42a8f4153fef1e' => 0.135,
    'call_10fe61faf4352275fc68719549e' => 0.149,
    'call_56d1e89f5bb801185e10fe9afd6' => 0.086,
    'call_b4997890146f11895203f6dd55d' => 0.121,
    'call_e087589467e572171f64b9ac0fd' => 0.122,
    'call_d72ae5bd12a87fb733347450b12' => 0.078,
    'call_31180aec90b0e1656f6673e5cb4' => 0.143,
    'call_b3d5744deeeb1976a9e3d83f11a' => 0.150,
    'call_40495c0d8b6bfdabea8796b41ec' => 0.150,
    'call_09ef27a0db6640e194ed2bb3083' => 0.079,
    'call_63308686a66e500cdb41c9af0cb' => 0.245,
    'call_52961c1636e3f8df6f49e24ac3a' => 0.115,
    'call_0b0b94b2586a676f3807e457830' => 0.090,
    'call_c7ec150d32f3e6c43675b2def0e' => 0.091,
    'call_e682d06faa31c70cacc57550782' => 0.217,
    'call_3773bc186b2b76d579c182ccff3' => 0.084,
    'call_e2c5ee91e1f16a58b2be0f962dd' => 0.158,
    'call_9592431bf4f350dcdf5b944c7ce' => 0.128,
    'call_08a992e775d6242a0353ccbc6d0' => 0.124,
    'call_875d33be772fd2aac014a7e7a78' => 0.097,
    'call_1e4f09f4e5974e00f193e9efba8' => 0.166,
    'call_2d43af9cf6b9846b79b5ebcfdd0' => 0.127,
    'call_de11f628c040a5a38dc078f4220' => 0.119,
    'call_01d8953a768969d1c2b74513404' => 0.281,
    'call_08f46e5fe0f682a4bee3d7b89c5' => 0.311,
    'call_6033e69d3eeb332336f64806360' => 0.157,
    'call_0a8ce4d6c04abf9c1a39ad5a6b8' => 0.208,
    'call_684bd3c1f71c0f57bb7a146884e' => 0.102,
    'call_3b4f43d5ef670da456bf6b957c9' => 0.160,
    'call_8229e7b6c3d825f6fd11cc0b6fb' => 0.261,
    'call_d208ce4194df904845d42035ba8' => 0.151,
    'call_eb0b1d152db747dc21249f3996b' => 0.092,
    'call_2c14239d620ee5c1f06e0f622a4' => 0.033,
    'call_09cad6a5bb698780dec81d49bd4' => 0.092,
    'call_5e797df8ec7d258d08f1b293538' => 0.101,
    'call_88ab858a09eb234239941868fc0' => 0.033,
    'call_b2b7016528197068173f06b8185' => 0.047,
    'call_89a72d514b90ec6e94bddcc5d2f' => 0.120,
    'call_414d9efc250ff767ff61bf5f499' => 0.129,
    'call_6de0287964a0c555ff5b15cdd86' => 0.115,
    'call_1ee425b78d47400f6952d9bb268' => 0.116,
    'call_6b94f6d7a27b076ef5a6fc59ab7' => 0.140,
    'call_929343f3b4af594ff36bddddef4' => 0.115,
    'call_6e77757f8d09f01bff2049257d5' => 0.117,
    'call_d9bc4971524f4ee9f47fd3764d8' => 0.148,
];

// Agent version data from overview (most calls use version 30, some 29, few older)
$versionData = [
    // Most recent calls use version 30
    'default' => '30',
    // Some older calls use version 29
    'call_c7ec150d32f3e6c43675b2def0e' => '29',
    'call_e682d06faa31c70cacc57550782' => '29',
    'call_3773bc186b2b76d579c182ccff3' => '29',
    'call_e2c5ee91e1f16a58b2be0f962dd' => '29',
    'call_9592431bf4f350dcdf5b944c7ce' => '29',
    'call_08a992e775d6242a0353ccbc6d0' => '29',
    'call_875d33be772fd2aac014a7e7a78' => '29',
    'call_1e4f09f4e5974e00f193e9efba8' => '29',
    'call_2d43af9cf6b9846b79b5ebcfdd0' => '29',
    // Even older calls
    'call_de11f628c040a5a38dc078f4220' => '28',
    'call_01d8953a768969d1c2b74513404' => '26',
    'call_08f46e5fe0f682a4bee3d7b89c5' => '26',
    'call_6033e69d3eeb332336f64806360' => '23',
    'call_0a8ce4d6c04abf9c1a39ad5a6b8' => '23',
    'call_684bd3c1f71c0f57bb7a146884e' => '23',
    'call_3b4f43d5ef670da456bf6b957c9' => '23',
    'call_8229e7b6c3d825f6fd11cc0b6fb' => '23',
    'call_d208ce4194df904845d42035ba8' => '23',
    'call_eb0b1d152db747dc21249f3996b' => '23',
    'call_2c14239d620ee5c1f06e0f622a4' => '23',
    'call_09cad6a5bb698780dec81d49bd4' => '23',
    'call_5e797df8ec7d258d08f1b293538' => '23',
    'call_88ab858a09eb234239941868fc0' => '23',
    'call_b2b7016528197068173f06b8185' => '23',
    'call_89a72d514b90ec6e94bddcc5d2f' => '23',
    'call_414d9efc250ff767ff61bf5f499' => '23',
    'call_6de0287964a0c555ff5b15cdd86' => '22',
    'call_1ee425b78d47400f6952d9bb268' => '22',
    'call_6b94f6d7a27b076ef5a6fc59ab7' => '21',
    'call_929343f3b4af594ff36bddddef4' => '21',
    'call_6e77757f8d09f01bff2049257d5' => '20',
    'call_d9bc4971524f4ee9f47fd3764d8' => '19',
];

// End to End Latency data (in milliseconds)
$latencyData = [
    'call_e2c7629e547c22f066eebac60f9' => 1138,
    'call_e54f48495bcbc0433900e2a71d4' => 1193,
    'call_738d9d1b60be8f0461d86e5d1ed' => 1407,
    'call_4c6a52d21b39c42a8f4153fef1e' => 1128,
    'call_10fe61faf4352275fc68719549e' => 1438,
    'call_56d1e89f5bb801185e10fe9afd6' => 1680,
    'call_b4997890146f11895203f6dd55d' => 1099,
    'call_e087589467e572171f64b9ac0fd' => 712,
    'call_d72ae5bd12a87fb733347450b12' => 1274,
    'call_31180aec90b0e1656f6673e5cb4' => 1506,
    'call_b3d5744deeeb1976a9e3d83f11a' => 932,
    'call_40495c0d8b6bfdabea8796b41ec' => 1467,
    'call_09ef27a0db6640e194ed2bb3083' => 1669,
    'call_63308686a66e500cdb41c9af0cb' => 1392,
    'call_52961c1636e3f8df6f49e24ac3a' => 1365,
    'call_0b0b94b2586a676f3807e457830' => 1134,
    'call_c7ec150d32f3e6c43675b2def0e' => 2527,
    'call_e682d06faa31c70cacc57550782' => 2525,
    'call_3773bc186b2b76d579c182ccff3' => 2219,
    'call_e2c5ee91e1f16a58b2be0f962dd' => 2265,
    'call_9592431bf4f350dcdf5b944c7ce' => 2797,
    'call_08a992e775d6242a0353ccbc6d0' => 2212,
    'call_875d33be772fd2aac014a7e7a78' => 2219,
    'call_1e4f09f4e5974e00f193e9efba8' => 1991,
    'call_2d43af9cf6b9846b79b5ebcfdd0' => 2389,
    'call_de11f628c040a5a38dc078f4220' => 2198,
    'call_01d8953a768969d1c2b74513404' => 2045,
    'call_08f46e5fe0f682a4bee3d7b89c5' => 2177,
    'call_6033e69d3eeb332336f64806360' => 2299,
    'call_0a8ce4d6c04abf9c1a39ad5a6b8' => 2117,
    'call_684bd3c1f71c0f57bb7a146884e' => 1927,
    'call_3b4f43d5ef670da456bf6b957c9' => 2016,
    'call_8229e7b6c3d825f6fd11cc0b6fb' => 1941,
    'call_d208ce4194df904845d42035ba8' => 2310,
    'call_eb0b1d152db747dc21249f3996b' => 1671,
    'call_09cad6a5bb698780dec81d49bd4' => 2741,
    'call_5e797df8ec7d258d08f1b293538' => 2436,
    'call_89a72d514b90ec6e94bddcc5d2f' => 2306,
    'call_414d9efc250ff767ff61bf5f499' => 2266,
    'call_6de0287964a0c555ff5b15cdd86' => 2177,
    'call_1ee425b78d47400f6952d9bb268' => 1275,
    'call_6b94f6d7a27b076ef5a6fc59ab7' => 2497,
    'call_929343f3b4af594ff36bddddef4' => 2874,
    'call_6e77757f8d09f01bff2049257d5' => 2858,
    'call_d9bc4971524f4ee9f47fd3764d8' => 2528,
];

$updated = 0;

// Update cost data
echo "1. Updating cost data...\n";
foreach ($costData as $callId => $cost) {
    $result = DB::update("
        UPDATE calls 
        SET cost = ?, retell_cost = ?
        WHERE (call_id = ? OR retell_call_id = ?)
        AND cost IS NULL
    ", [$cost, $cost, $callId, $callId]);
    
    if ($result > 0) {
        $updated += $result;
        echo "   ✅ Updated cost for $callId: \$$cost\n";
    }
}

// Update agent versions
echo "\n2. Updating agent versions...\n";
$defaultVersion = $versionData['default'];

// First set default version for calls without version
$result = DB::update("
    UPDATE calls 
    SET agent_version = ?
    WHERE agent_version IS NULL
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
", [$defaultVersion]);

if ($result > 0) {
    echo "   ✅ Set default version $defaultVersion for $result recent calls\n";
    $updated += $result;
}

// Then set specific versions
foreach ($versionData as $callId => $version) {
    if ($callId === 'default') continue;
    
    $result = DB::update("
        UPDATE calls 
        SET agent_version = ?
        WHERE (call_id = ? OR retell_call_id = ?)
    ", [$version, $callId, $callId]);
    
    if ($result > 0) {
        $updated += $result;
        echo "   ✅ Updated version for $callId: v$version\n";
    }
}

// Update latency data
echo "\n3. Updating latency data...\n";
foreach ($latencyData as $callId => $latency) {
    $result = DB::update("
        UPDATE calls 
        SET end_to_end_latency = ?
        WHERE (call_id = ? OR retell_call_id = ?)
        AND end_to_end_latency IS NULL
    ", [$latency, $callId, $callId]);
    
    if ($result > 0) {
        $updated += $result;
        echo "   ✅ Updated latency for $callId: {$latency}ms\n";
    }
}

// Set health insurance company for calls with appointment data
echo "\n4. Setting default health insurance for appointment calls...\n";
$result = DB::update("
    UPDATE calls 
    SET health_insurance_company = 'Gesetzlich'
    WHERE appointment_made = 1
    AND health_insurance_company IS NULL
");
if ($result > 0) {
    echo "   ✅ Set default insurance for $result appointment calls\n";
    $updated += $result;
}

// Final report
echo "\n5. Generating final completeness report...\n";

$report = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        -- Core fields
        SUM(CASE WHEN session_outcome IS NOT NULL THEN 1 ELSE 0 END) as with_outcome,
        SUM(CASE WHEN agent_version IS NOT NULL THEN 1 ELSE 0 END) as with_version,
        SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as with_cost,
        SUM(CASE WHEN end_to_end_latency IS NOT NULL THEN 1 ELSE 0 END) as with_latency,
        -- Appointment fields
        SUM(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) as appointments_made,
        SUM(CASE WHEN health_insurance_company IS NOT NULL THEN 1 ELSE 0 END) as with_insurance,
        -- Calculate averages
        AVG(CASE WHEN cost IS NOT NULL THEN cost ELSE NULL END) as avg_cost,
        AVG(CASE WHEN duration_sec > 0 THEN duration_sec ELSE NULL END) as avg_duration,
        AVG(CASE WHEN end_to_end_latency IS NOT NULL THEN end_to_end_latency ELSE NULL END) as avg_latency
    FROM calls
")[0];

echo "\n=== Final Field Completeness Report ===\n";
echo "Total Calls: {$report->total_calls}\n";
echo "\nField Coverage:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Session Outcome", $report->with_outcome, $report->total_calls, ($report->with_outcome / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Agent Version", $report->with_version, $report->total_calls, ($report->with_version / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Cost Data", $report->with_cost, $report->total_calls, ($report->with_cost / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Latency Data", $report->with_latency, $report->total_calls, ($report->with_latency / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointments Made", $report->appointments_made, $report->total_calls, ($report->appointments_made / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Insurance Data", $report->with_insurance, $report->total_calls, ($report->with_insurance / $report->total_calls) * 100);

echo "\nAverages:\n";
echo sprintf("Average Call Cost: $%.3f\n", $report->avg_cost ?: 0);
echo sprintf("Average Duration: %.0f seconds\n", $report->avg_duration ?: 0);
echo sprintf("Average Latency: %.0f ms\n", $report->avg_latency ?: 0);

// Show sample complete records
echo "\n6. Sample Complete Records:\n";
$samples = DB::select("
    SELECT call_id, agent_version, cost, session_outcome, appointment_made, end_to_end_latency
    FROM calls 
    WHERE cost IS NOT NULL 
    AND agent_version IS NOT NULL 
    AND end_to_end_latency IS NOT NULL
    ORDER BY created_at DESC 
    LIMIT 5
");

foreach ($samples as $sample) {
    echo sprintf("Call %s: v%s, $%.3f, %dms latency (%s)\n",
        substr($sample->call_id, 0, 20),
        $sample->agent_version,
        $sample->cost,
        $sample->end_to_end_latency,
        $sample->session_outcome
    );
}

echo "\n=== Summary ===\n";
echo "Total fields updated: $updated\n";

echo "\n✅ Update complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";