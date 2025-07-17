<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Services\TranslationService;

echo "Updating Call 240 language detection...\n";

$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(240);

if (!$call) {
    echo "Call not found!\n";
    exit(1);
}

// Get transcript
$transcript = $call->transcript ?? '';
$summary = $call->webhook_data['call_analysis']['call_summary'] ?? '';

// Combine texts for better detection
$textToAnalyze = $transcript . ' ' . $summary;

// Use TranslationService to detect language
$translator = app(TranslationService::class);
$detectedLang = $translator->detectLanguage($textToAnalyze);

echo "Transcript excerpt: " . substr($transcript, 0, 200) . "...\n";
echo "Detected language: $detectedLang\n";

// Update the call
$call->detected_language = $detectedLang;
$call->language_confidence = 0.95;
$call->language_mismatch = ($detectedLang !== 'de'); // Company expects German
$call->save();

echo "Call updated successfully!\n";
echo "- Language: {$call->detected_language}\n";
echo "- Confidence: {$call->language_confidence}\n";
echo "- Mismatch: " . ($call->language_mismatch ? 'Yes' : 'No') . "\n";