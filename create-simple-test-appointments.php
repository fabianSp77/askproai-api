<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use Carbon\Carbon;

// Direkt Termine erstellen ohne komplexe Beziehungen
$appointments = [
    [
        'company_id' => 1,
        'customer_id' => 18, // Anna Schmidt
        'starts_at' => Carbon::today()->setHour(9),
        'ends_at' => Carbon::today()->setHour(10),
        'status' => 'confirmed',
        'source' => 'phone',
        'notes' => 'Testtermin 1'
    ],
    [
        'company_id' => 1,
        'customer_id' => 19, // Anna Schmidt
        'starts_at' => Carbon::today()->setHour(11),
        'ends_at' => Carbon::today()->setHour(12),
        'status' => 'confirmed',
        'source' => 'phone',
        'notes' => 'Testtermin 2'
    ],
    [
        'company_id' => 1,
        'customer_id' => 7, // Hans Schuster
        'starts_at' => Carbon::today()->setHour(14),
        'ends_at' => Carbon::today()->setHour(15),
        'status' => 'pending',
        'source' => 'phone',
        'notes' => 'Testtermin 3'
    ],
    [
        'company_id' => 1,
        'customer_id' => 21, // Julia Weber
        'starts_at' => Carbon::tomorrow()->setHour(10),
        'ends_at' => Carbon::tomorrow()->setHour(11),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Testtermin 4'
    ],
    [
        'company_id' => 1,
        'customer_id' => 20, // Peter Müller
        'starts_at' => Carbon::tomorrow()->setHour(15),
        'ends_at' => Carbon::tomorrow()->setHour(16),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Testtermin 5'
    ]
];

// Set company context
app()->instance('current_company_id', 1);

foreach ($appointments as $data) {
    try {
        $appointment = Appointment::create($data);
        echo "Created appointment: {$appointment->id} - {$data['notes']} - Status: {$data['status']}\n";
    } catch (\Exception $e) {
        echo "Error creating appointment: " . $e->getMessage() . "\n";
    }
}

echo "\nSimple test appointments created!\n";