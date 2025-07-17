<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Call;
use App\Models\Customer;
use App\Services\Customer\CustomerMatchingService;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

// Setze Umgebung für Batch-Verarbeitung
app()->instance('running_in_batch', true);

echo "=== DATENBEREINIGUNG STARTET ===\n\n";

// 1. Calls ohne customer_id aber mit Kundendaten zuordnen
echo "1. Verarbeite Calls ohne customer_id...\n";
$callsWithoutCustomer = Call::withoutGlobalScopes()
    ->whereNull('customer_id')
    ->where(function($q) {
        $q->whereNotNull('metadata->customer_data->name')
          ->orWhereNotNull('metadata->customer_data->company')
          ->orWhereNotNull('metadata->customer_data->email');
    })
    ->get();

$matchingService = app(CustomerMatchingService::class);
$assigned = 0;

foreach ($callsWithoutCustomer as $call) {
    $customerData = $call->metadata['customer_data'] ?? [];
    
    if (empty($customerData)) continue;
    
    // Versuche existierenden Kunden zu finden
    $phoneNumber = $call->from_number;
    $companyName = $customerData['company'] ?? null;
    $customerNumber = $customerData['customer_number'] ?? null;
    $name = $customerData['name'] ?? $customerData['full_name'] ?? null;
    $email = $customerData['email'] ?? null;
    
    // Suche nach Übereinstimmungen
    $matches = $matchingService->findRelatedCustomers(
        $call->company_id,
        $call->to_number,
        $phoneNumber,
        $companyName,
        $customerNumber
    );
    
    if ($matches->count() > 0 && $matches->first()->match_confidence >= 90) {
        // Hohe Übereinstimmung gefunden - automatisch zuordnen
        $customer = $matches->first();
        $call->customer_id = $customer->id;
        $call->save();
        $assigned++;
        echo "   - Call {$call->id} zugeordnet zu Customer {$customer->id} ({$customer->name})\n";
    } else {
        // Kein guter Match - neuen Kunden erstellen
        $customer = Customer::create([
            'company_id' => $call->company_id,
            'name' => $name ?: 'Unbekannt',
            'email' => $email,
            'phone' => $phoneNumber,
            'company_name' => $companyName,
            'customer_number' => $customerNumber,
            'journey_status' => 'initial_contact',
            'created_at' => $call->created_at
        ]);
        
        $call->customer_id = $customer->id;
        $call->save();
        $assigned++;
        echo "   - Call {$call->id}: Neuer Kunde erstellt (ID: {$customer->id})\n";
    }
}

echo "   -> {$assigned} Calls zugeordnet\n\n";

// 2. Doppelte Telefonnummern zusammenführen
echo "2. Bereinige doppelte Telefonnummern...\n";
$duplicatePhones = DB::table('customers')
    ->select('phone', 'company_id', DB::raw('COUNT(*) as count'))
    ->whereNotNull('phone')
    ->groupBy('phone', 'company_id')
    ->having('count', '>', 1)
    ->get();

$merged = 0;
foreach ($duplicatePhones as $dup) {
    $customers = Customer::withoutGlobalScopes()
        ->where('company_id', $dup->company_id)
        ->where('phone', $dup->phone)
        ->orderBy('created_at', 'asc')
        ->get();
    
    if ($customers->count() <= 1) continue;
    
    // Behalte den ältesten Kunden
    $primaryCustomer = $customers->first();
    
    foreach ($customers->skip(1) as $duplicateCustomer) {
        // Übertrage wichtige Daten
        if (empty($primaryCustomer->company_name) && !empty($duplicateCustomer->company_name)) {
            $primaryCustomer->company_name = $duplicateCustomer->company_name;
        }
        if (empty($primaryCustomer->email) && !empty($duplicateCustomer->email)) {
            $primaryCustomer->email = $duplicateCustomer->email;
        }
        if (empty($primaryCustomer->customer_number) && !empty($duplicateCustomer->customer_number)) {
            $primaryCustomer->customer_number = $duplicateCustomer->customer_number;
        }
        
        // Aktualisiere Call-Zuordnungen
        Call::withoutGlobalScopes()
            ->where('customer_id', $duplicateCustomer->id)
            ->update(['customer_id' => $primaryCustomer->id]);
        
        // Aktualisiere Appointment-Zuordnungen
        DB::table('appointments')
            ->where('customer_id', $duplicateCustomer->id)
            ->update(['customer_id' => $primaryCustomer->id]);
        
        // Lösche Duplikat
        $duplicateCustomer->delete();
        $merged++;
    }
    
    $primaryCustomer->save();
    echo "   - {$dup->phone}: {$customers->count()} Kunden zu 1 zusammengeführt\n";
}

echo "   -> {$merged} Duplikate entfernt\n\n";

// 3. Fehlende to_number ergänzen
echo "3. Ergänze fehlende to_number...\n";
$callsWithoutToNumber = Call::withoutGlobalScopes()
    ->where(function($q) {
        $q->whereNull('to_number')
          ->orWhere('to_number', '');
    })
    ->get();

$fixed = 0;
foreach ($callsWithoutToNumber as $call) {
    // Versuche aus webhook_data zu extrahieren
    if (!empty($call->webhook_data['to_number'])) {
        $call->to_number = $call->webhook_data['to_number'];
        $call->save();
        $fixed++;
    } elseif (!empty($call->webhook_data['call']['to_number'])) {
        $call->to_number = $call->webhook_data['call']['to_number'];
        $call->save();
        $fixed++;
    }
}

echo "   -> {$fixed} to_number ergänzt\n\n";

// 4. Aktualisiere Call- und Appointment-Zähler
echo "4. Aktualisiere Kundenzähler...\n";
$customers = Customer::withoutGlobalScopes()->get();
$updated = 0;

foreach ($customers as $customer) {
    $callCount = Call::withoutGlobalScopes()
        ->where('customer_id', $customer->id)
        ->count();
    
    $appointmentCount = DB::table('appointments')
        ->where('customer_id', $customer->id)
        ->count();
    
    $lastCall = Call::withoutGlobalScopes()
        ->where('customer_id', $customer->id)
        ->orderBy('start_timestamp', 'desc')
        ->first();
    
    $lastAppointment = DB::table('appointments')
        ->where('customer_id', $customer->id)
        ->orderBy('starts_at', 'desc')
        ->first();
    
    $changes = false;
    
    if ($customer->call_count != $callCount) {
        $customer->call_count = $callCount;
        $changes = true;
    }
    
    if ($customer->appointment_count != $appointmentCount) {
        $customer->appointment_count = $appointmentCount;
        $changes = true;
    }
    
    if ($lastCall && $customer->last_call_at != ($lastCall->start_timestamp ?? $lastCall->created_at)) {
        $customer->last_call_at = $lastCall->start_timestamp ?? $lastCall->created_at;
        $changes = true;
    }
    
    if ($lastAppointment && $customer->last_appointment_at != $lastAppointment->starts_at) {
        $customer->last_appointment_at = $lastAppointment->starts_at;
        $changes = true;
    }
    
    if ($changes) {
        $customer->save();
        $updated++;
    }
}

echo "   -> {$updated} Kundenzähler aktualisiert\n\n";

// 5. Setze initiale Journey Status
echo "5. Setze initiale Journey Status...\n";
$customersWithoutStatus = Customer::withoutGlobalScopes()
    ->whereNull('journey_status')
    ->get();

foreach ($customersWithoutStatus as $customer) {
    if ($customer->appointment_count > 5) {
        $customer->journey_status = 'regular_customer';
    } elseif ($customer->appointment_count > 0) {
        $customer->journey_status = 'appointment_completed';
    } elseif ($customer->call_count > 0) {
        $customer->journey_status = 'initial_contact';
    } else {
        $customer->journey_status = 'initial_contact';
    }
    
    $customer->journey_status_updated_at = now();
    $customer->save();
}

echo "   -> {$customersWithoutStatus->count()} Journey Status gesetzt\n\n";

echo "=== BEREINIGUNG ABGESCHLOSSEN ===\n";
echo "Zusammenfassung:\n";
echo "- {$assigned} Calls zugeordnet\n";
echo "- {$merged} Duplikate entfernt\n";
echo "- {$fixed} to_number ergänzt\n";
echo "- {$updated} Kundenzähler aktualisiert\n";
echo "- {$customersWithoutStatus->count()} Journey Status gesetzt\n";