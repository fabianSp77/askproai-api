<?php

use App\Models\Appointment;
use App\Models\Company;
use App\Jobs\SendAppointmentEmailJob;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Direct Email Test\n";
echo "=================================\n\n";

// Set company context
$company = Company::first();
if (!$company) {
    echo "No companies found.\n";
    exit(1);
}

app()->instance('current_company_id', $company->id);
echo "Using company: {$company->name}\n";

// Get an appointment
$appointment = Appointment::with(['customer', 'staff', 'service', 'branch.company'])
    ->whereHas('customer', function ($query) {
        $query->whereNotNull('email');
    })
    ->where('company_id', $company->id)
    ->latest()
    ->first();

if (!$appointment) {
    echo "No appointments found with customer email.\n";
    exit(1);
}

echo "\nAppointment Details:\n";
echo "ID: {$appointment->id}\n";
echo "Customer: {$appointment->customer->first_name} {$appointment->customer->last_name}\n";
echo "Email: {$appointment->customer->email}\n";
echo "Date: {$appointment->starts_at->format('d.m.Y H:i')}\n";

// Test ICS generation first
echo "\nTesting ICS generation...\n";
try {
    $icsService = new \App\Services\IcsGeneratorService();
    $icsContent = $icsService->generateForAppointment($appointment);
    echo "ICS content generated successfully (" . strlen($icsContent) . " bytes)\n";
} catch (\Exception $e) {
    echo "ICS generation failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Test email creation
echo "\nTesting email creation...\n";
try {
    $mail = new \App\Mail\AppointmentConfirmationMail($appointment, 'de');
    echo "Mail object created successfully\n";
    
    // Test getting envelope
    $envelope = $mail->envelope();
    echo "Subject: " . $envelope->subject . "\n";
    
    // Test getting content
    $content = $mail->content();
    echo "View: " . $content->view . "\n";
    
} catch (\Exception $e) {
    echo "Email creation failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Test direct sending (synchronous)
echo "\nTesting direct email sending...\n";
try {
    \Illuminate\Support\Facades\Mail::to($appointment->customer->email)->send(new \App\Mail\AppointmentConfirmationMail($appointment, 'de'));
    echo "Email sent successfully!\n";
} catch (\Exception $e) {
    echo "Email sending failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nDone.\n";