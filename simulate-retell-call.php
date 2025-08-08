#!/usr/bin/env php
<?php
/**
 * Simulate a Retell MCP Call Flow
 * This simulates what Retell would do during a call
 */

echo "\n";
echo "================================================================================\n";
echo "                    📞 RETELL MCP CALL SIMULATION\n";
echo "================================================================================\n\n";

$baseUrl = 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp';

// Helper function for MCP requests
function mcpRequest($method, $params = []) {
    global $baseUrl;
    
    $payload = [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => $method,
        'params' => $params
    ];
    
    $ch = curl_init($baseUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => "HTTP $httpCode"];
    }
    
    $data = json_decode($response, true);
    return $data['result'] ?? ['error' => 'Invalid response'];
}

// Simulate call flow
echo "🤖 AI: Guten Tag! Willkommen beim Friseursalon. Wie kann ich Ihnen helfen?\n";
echo "👤 Kunde: Ich möchte einen Termin für einen Haarschnitt buchen.\n\n";

// Step 1: List services
echo "1️⃣ LISTING SERVICES...\n";
$services = mcpRequest('list_services', ['company_id' => 1]);

if (isset($services['services'])) {
    echo "   ✅ Found " . count($services['services']) . " services\n";
    
    // Find haircut services
    $hairServices = array_filter($services['services'], function($s) {
        return stripos($s['name'], 'haar') !== false || 
               stripos($s['name'], 'schnitt') !== false;
    });
    
    if (count($hairServices) > 0) {
        echo "\n🤖 AI: Wir haben folgende Haarschnitt-Services:\n";
        foreach ($hairServices as $service) {
            echo "   - {$service['name']} ({$service['price']}€, {$service['duration']} Min.)\n";
        }
        
        // Pick first haircut service
        $selectedService = reset($hairServices);
        echo "\n👤 Kunde: Ich nehme {$selectedService['name']}\n\n";
        
        // Step 2: Check availability
        echo "2️⃣ CHECKING AVAILABILITY...\n";
        $availability = mcpRequest('check_availability', [
            'company_id' => 1,
            'service_id' => $selectedService['id'],
            'date' => date('Y-m-d')
        ]);
        
        if (isset($availability['available_slots']) && count($availability['available_slots']) > 0) {
            echo "   ✅ Found " . count($availability['available_slots']) . " available slots\n";
            echo "\n🤖 AI: Verfügbare Termine heute:\n";
            foreach (array_slice($availability['available_slots'], 0, 3) as $slot) {
                echo "   - {$slot['time']} mit {$slot['staff_name']}\n";
            }
            
            $selectedSlot = $availability['available_slots'][0];
            echo "\n👤 Kunde: Ich nehme {$selectedSlot['time']} Uhr\n";
            echo "🤖 AI: Wie ist Ihr Name?\n";
            echo "👤 Kunde: Max Mustermann\n";
            echo "🤖 AI: Und Ihre Telefonnummer?\n";
            echo "👤 Kunde: 0170-1234567\n\n";
            
            // Step 3: Book appointment
            echo "3️⃣ BOOKING APPOINTMENT...\n";
            $booking = mcpRequest('book_appointment', [
                'company_id' => 1,
                'customer_name' => 'Max Mustermann',
                'customer_phone' => '0170-1234567',
                'service_id' => $selectedService['id'],
                'staff_id' => $selectedSlot['staff_id'],
                'datetime' => $selectedSlot['datetime'] ?? date('Y-m-d') . ' ' . $selectedSlot['time']
            ]);
            
            if (isset($booking['booking_confirmed']) && $booking['booking_confirmed']) {
                echo "   ✅ Appointment booked successfully!\n";
                echo "\n🤖 AI: {$booking['message']}\n";
                echo "   Termin: {$booking['datetime']}\n";
                echo "   Service: {$booking['service']}\n";
                echo "   Mitarbeiter: {$booking['staff']}\n";
            } else {
                echo "   ❌ Booking failed: " . json_encode($booking) . "\n";
            }
        } else {
            echo "   ⚠️  No available slots found\n";
            echo "   Response: " . json_encode($availability) . "\n";
        }
    } else {
        echo "   ⚠️  No haircut services found\n";
        echo "   Available services:\n";
        foreach (array_slice($services['services'], 0, 5) as $s) {
            echo "   - {$s['name']}\n";
        }
    }
} else {
    echo "   ❌ Failed to get services\n";
    echo "   Response: " . json_encode($services) . "\n";
}

echo "\n================================================================================\n";
echo "                    📊 SIMULATION COMPLETE\n";
echo "================================================================================\n\n";

// Check database for appointment
echo "Checking database for appointments...\n";
exec('mysql -u askproai_user -p\'lkZ57Dju9EDjrMxn\' askproai_db -e "SELECT id, customer_id, service_id, starts_at FROM appointments WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) ORDER BY created_at DESC LIMIT 1;" 2>/dev/null', $output);
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n✨ This simulation shows what should happen during a real Retell call.\n";
echo "If the real call doesn't work, check:\n";
echo "1. Is the phone number connected to Retell?\n";
echo "2. Is the MCP URL configured in the Retell agent?\n";
echo "3. Are there any errors in the Retell dashboard?\n";
echo "\n";