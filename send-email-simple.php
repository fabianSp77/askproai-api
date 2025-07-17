<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Sende E-Mail EINFACH ===\n\n";

// Use simple mail sending
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);

if (!$call) {
    echo "❌ Call 228 nicht gefunden!\n";
    exit(1);
}

echo "Call gefunden: {$call->id}\n";
echo "Sende E-Mail an: fabianspitzer@icloud.com\n\n";

// Send directly via Resend API
$apiKey = config('services.resend.key');
$timestamp = now()->format('d.m.Y H:i:s');

$emailHtml = view('emails.call-summary', [
    'call' => $call,
    'company' => $call->company,
    'includeTranscript' => true,
    'customMessage' => "Business Portal E-Mail - Direktversand\nZeit: $timestamp",
    'sender_name' => $call->company->name,
    'sender_email' => 'info@askproai.de',
    'recipientType' => 'internal',
    'callDuration' => gmdate('i:s', $call->duration_sec ?? 0),
    'hasAppointment' => $call->appointment_id !== null,
    'urgencyLevel' => $call->custom_analysis_data['urgency_level'] ?? null,
    'actionItems' => []
])->render();

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

// Create CSV attachment
$csvService = new \App\Services\CallExportService();
$csvContent = $csvService->exportSingleCall($call);
$csvBase64 = base64_encode($csvContent);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => 'info@askproai.de',
    'to' => 'fabianspitzer@icloud.com',
    'subject' => 'Anrufzusammenfassung - ' . ($call->customer?->name ?? 'Unbekannt') . ' - ' . $call->created_at->format('d.m.Y H:i'),
    'html' => $emailHtml,
    'attachments' => [
        [
            'filename' => 'anruf_' . $call->id . '_' . $call->created_at->format('Y-m-d') . '.csv',
            'content' => $csvBase64
        ]
    ]
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode == 200) {
    echo "✅ E-Mail erfolgreich versendet!\n";
    echo "Response: $response\n\n";
    
    // Log activity
    \App\Models\CallActivity::log($call, \App\Models\CallActivity::TYPE_EMAIL_SENT, 'Direktversand via Script', [
        'user_id' => 1,
        'is_system' => false,
        'description' => "E-Mail direkt versendet an fabianspitzer@icloud.com",
        'metadata' => [
            'recipients' => ['fabianspitzer@icloud.com'],
            'method' => 'direct_api',
            'sent_at' => $timestamp
        ]
    ]);
    
    echo "Die E-Mail sollte SOFORT ankommen!\n";
    echo "Enthält:\n";
    echo "- HTML-Design ✅\n";
    echo "- Transkript ✅\n";
    echo "- CSV-Anhang ✅\n";
} else {
    echo "❌ Fehler beim Versand!\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    if ($error) {
        echo "cURL Error: $error\n";
    }
}