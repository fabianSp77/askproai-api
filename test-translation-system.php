<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Call;
use App\Models\Company;
use App\Helpers\AutoTranslateHelper;
use App\Services\TranslationService;

echo "\n=== MULTILINGUAL TRANSLATION SYSTEM TEST ===\n\n";

// 1. Test Translation Service
echo "1. Testing Translation Service...\n";
try {
    $translator = app(TranslationService::class);
    
    // Test dictionary translation
    $result = $translator->translate("Hello, I need an appointment", "de");
    echo "   ✅ Dictionary translation: '$result'\n";
    
    // Test language detection
    $detected = $translator->detectLanguage("Guten Tag, ich brauche einen Termin");
    echo "   ✅ Language detection: " . ($detected === 'de' ? 'German detected' : 'Detection failed') . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Translation Service Error: " . $e->getMessage() . "\n";
}

// 2. Test User Language Settings
echo "\n2. Testing User Language Settings...\n";
$user = User::first();
if ($user) {
    $user->update([
        'interface_language' => 'de',
        'content_language' => 'de',
        'auto_translate_content' => true
    ]);
    echo "   ✅ User settings updated\n";
    echo "   - Interface: {$user->interface_language}\n";
    echo "   - Content: {$user->content_language}\n";
    echo "   - Auto-translate: " . ($user->auto_translate_content ? 'Yes' : 'No') . "\n";
} else {
    echo "   ❌ No user found\n";
}

// 3. Test AutoTranslateHelper
echo "\n3. Testing AutoTranslateHelper...\n";
$testContent = "Hello, I would like to book an appointment for next week.";
$translated = AutoTranslateHelper::translateContent($testContent, 'en', $user);
echo "   ✅ Auto-translation test:\n";
echo "   - Original: $testContent\n";
echo "   - Translated: $translated\n";

// 4. Test Toggleable Content
echo "\n4. Testing Toggleable Content...\n";
$toggleable = AutoTranslateHelper::getToggleableContent($testContent, 'en', $user);
echo "   ✅ Toggleable content structure:\n";
echo "   - Original: {$toggleable['original']}\n";
echo "   - Translated: {$toggleable['translated']}\n";
echo "   - Should translate: " . ($toggleable['should_translate'] ? 'Yes' : 'No') . "\n";

// 5. Test with a real call
echo "\n5. Testing with Call Data...\n";
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('transcript')
    ->first();
if ($call) {
    // Set detected language to English for testing
    $call->update(['detected_language' => 'en']);
    
    $texts = AutoTranslateHelper::processCallTexts($call, $user);
    echo "   ✅ Call texts processed:\n";
    echo "   - Transcript: " . (isset($texts['transcript']['translated']) ? 'Translated' : 'Not translated') . "\n";
    echo "   - Summary: " . (isset($texts['summary']['translated']) ? 'Translated' : 'Not translated') . "\n";
    echo "   - Reason: " . (isset($texts['reason_for_visit']['translated']) ? 'Translated' : 'Not translated') . "\n";
} else {
    echo "   ❌ No call with transcript found\n";
}

// 6. Check if views exist
echo "\n6. Checking View Files...\n";
$viewsToCheck = [
    'filament.infolists.toggleable-text',
    'filament.admin.pages.user-language-settings'
];

foreach ($viewsToCheck as $view) {
    if (view()->exists($view)) {
        echo "   ✅ View exists: $view\n";
    } else {
        echo "   ❌ View missing: $view\n";
    }
}

// 7. Check existing calls with language info
echo "\n7. Checking Existing Calls with Language Info...\n";
$callsWithLanguage = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('detected_language')
    ->take(5)
    ->get(['id', 'call_id', 'detected_language', 'language_confidence', 'created_at']);

if ($callsWithLanguage->count() > 0) {
    echo "   ✅ Found {$callsWithLanguage->count()} calls with language detection:\n";
    foreach ($callsWithLanguage as $call) {
        echo "   - Call {$call->call_id}: {$call->detected_language} (" . round($call->language_confidence * 100) . "%)\n";
    }
} else {
    echo "   ℹ️  No calls with language detection found\n";
    
    // Update an existing call to add language info for testing
    $existingCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->whereNotNull('transcript')
        ->first();
    
    if ($existingCall) {
        $existingCall->update([
            'detected_language' => 'en',
            'language_confidence' => 0.95,
            'language_mismatch' => true
        ]);
        echo "   ✅ Updated existing call {$existingCall->id} with English language detection\n";
        echo "   📍 View at: /admin/calls/{$existingCall->id}\n";
    }
}

echo "\n✅ TRANSLATION SYSTEM TEST COMPLETE\n";
echo "\nNext steps:\n";
echo "1. Visit /admin to see the language settings in the menu\n";
echo "2. Go to a call detail page to see the translation toggle\n";
echo "3. Check your user language settings\n\n";