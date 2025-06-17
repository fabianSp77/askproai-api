<?php

use App\Models\Call;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Hole einen Call mit Kosten
$call = Call::whereNotNull('cost')
    ->latest()
    ->first();

if (!$call) {
    echo "Kein Call mit Kosten gefunden.\n";
    exit;
}

echo "=== KOSTENDATEN AUS DER DATENBANK ===\n";
echo "Call ID: " . $call->id . "\n";
echo "Duration: " . $call->duration_sec . " Sekunden (" . round($call->duration_sec / 60, 2) . " Minuten)\n";
echo "Cost in DB: €" . number_format($call->cost, 2) . "\n";
echo "Cost Breakdown: " . json_encode($call->cost_breakdown ?? [], JSON_PRETTY_PRINT) . "\n\n";

// Hole Daten von Retell
$callId = $call->retell_call_id ?? $call->call_id;
if ($callId) {
    $token = config('services.retell.api_key');
    $url = "https://api.retellai.com/v2/get-call/{$callId}";
    
    $response = Http::withToken($token)->get($url);
    
    if ($response->successful()) {
        $data = $response->json();
        
        echo "=== KOSTENDATEN VON RETELL API ===\n";
        echo "Duration MS: " . $data['duration_ms'] . " (" . round($data['duration_ms'] / 1000 / 60, 2) . " Minuten)\n";
        echo "Call Cost Object:\n";
        print_r($data['call_cost']);
        
        echo "\n=== KOSTENBERECHNUNG ===\n";
        $costData = $data['call_cost'];
        
        // Die Werte von Retell sind in Cents, nicht Dollar!
        $combinedCostCents = $costData['combined_cost'] ?? 0;
        $combinedCostDollars = $combinedCostCents / 100;
        
        echo "Combined Cost (cents): " . $combinedCostCents . "\n";
        echo "Combined Cost (dollars): $" . number_format($combinedCostDollars, 4) . "\n";
        echo "Combined Cost (euros @ 0.92): €" . number_format($combinedCostDollars * 0.92, 4) . "\n\n";
        
        echo "Product Costs:\n";
        foreach ($costData['product_costs'] as $product) {
            $costCents = $product['cost'];
            $costDollars = $costCents / 100;
            echo "- " . $product['product'] . ": " . $costCents . " cents = $" . number_format($costDollars, 4) . "\n";
        }
        
        echo "\n=== VERGLEICH MIT DEINEM BEISPIEL ===\n";
        echo "Dein Beispiel: Total cost $0.128 für 1.467 Minuten\n";
        echo "Pro Minute: $" . number_format(0.128 / 1.467, 4) . "\n";
        
        $retellMinutes = $data['duration_ms'] / 1000 / 60;
        $retellCostPerMinute = $combinedCostDollars / $retellMinutes;
        echo "\nRetell Daten: $" . number_format($combinedCostDollars, 4) . " für " . round($retellMinutes, 3) . " Minuten\n";
        echo "Pro Minute: $" . number_format($retellCostPerMinute, 4) . "\n";
    }
}

// Prüfe was in cost_breakdown steht
echo "\n=== COST BREAKDOWN ANALYSE ===\n";
if (isset($call->cost_breakdown) && is_array($call->cost_breakdown)) {
    $breakdown = $call->cost_breakdown;
    
    if (isset($breakdown['combined_cost'])) {
        echo "Combined Cost im Breakdown: " . $breakdown['combined_cost'] . "\n";
        echo "Ist das Cents? " . $breakdown['combined_cost'] . " cents = $" . number_format($breakdown['combined_cost'] / 100, 4) . "\n";
    }
}