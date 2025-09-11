#!/usr/bin/env php
<?php

use App\Filament\Admin\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\User;

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Authenticate as admin
$admin = User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    echo "❌ Admin user not found\n";
    exit(1);
}

auth()->loginUsingId($admin->id);
echo "✅ Authenticated as: {$admin->email}\n\n";

// Test 1: Check if appointments exist
$appointmentCount = Appointment::count();
echo "📊 Total appointments in database: {$appointmentCount}\n";

// Test 2: Check appointments with relations
$appointmentsWithRelations = Appointment::with(['customer', 'staff', 'service', 'calcomEventType', 'branch'])->limit(5)->get();
echo "📋 Sample appointments with relations:\n";
foreach ($appointmentsWithRelations as $appointment) {
    echo "  - ID: {$appointment->id}, Cal.com ID: {$appointment->calcom_v2_booking_id}, Customer: " . 
         ($appointment->customer ? $appointment->customer->name : 'N/A') . 
         ", Status: {$appointment->status}\n";
}

// Test 3: Check using Resource's Eloquent Query
echo "\n🔍 Testing AppointmentResource query:\n";
try {
    $resourceQuery = AppointmentResource::getEloquentQuery();
    $resourceAppointments = $resourceQuery->limit(5)->get();
    echo "✅ Resource query successful - Found {$resourceAppointments->count()} appointments\n";
    
    foreach ($resourceAppointments as $appointment) {
        echo "  - {$appointment->calcom_v2_booking_id}: " . 
             ($appointment->customer ? $appointment->customer->name : 'No customer') . 
             " on " . ($appointment->starts_at ? $appointment->starts_at->format('d.m.Y H:i') : 'No date') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Resource query failed: " . $e->getMessage() . "\n";
}

// Test 4: Check table columns configuration
echo "\n📊 Checking table configuration:\n";
try {
    $table = AppointmentResource::table(new \Filament\Tables\Table());
    $columns = $table->getColumns();
    echo "✅ Table has " . count($columns) . " columns configured\n";
    echo "Columns: ";
    foreach ($columns as $column) {
        echo $column->getName() . ", ";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Table configuration error: " . $e->getMessage() . "\n";
}

// Test 5: Check permissions
echo "\n🔐 Permission checks:\n";
echo "  - Can view any: " . (AppointmentResource::canViewAny() ? '✅ Yes' : '❌ No') . "\n";
$firstAppointment = Appointment::first();
if ($firstAppointment) {
    echo "  - Can view record: " . (AppointmentResource::canView($firstAppointment) ? '✅ Yes' : '❌ No') . "\n";
}

echo "\n✨ Test complete!\n";