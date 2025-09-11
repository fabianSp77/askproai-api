<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Helpers\GermanFormatter;
use Carbon\Carbon;

// Set timezone and locale
date_default_timezone_set('Europe/Berlin');
Carbon::setLocale('de');

echo "🇩🇪 TEST DER DEUTSCHEN FORMATIERUNG\n";
echo "=====================================\n\n";

// Test Currency Formatting
echo "WÄHRUNGSFORMATIERUNG:\n";
echo "---------------------\n";
echo "1234.56 => " . GermanFormatter::formatCurrency(1234.56) . " (erwartet: 1.234,56 €)\n";
echo "0.99 => " . GermanFormatter::formatCurrency(0.99) . " (erwartet: 0,99 €)\n";
echo "1000000 => " . GermanFormatter::formatCurrency(1000000) . " (erwartet: 1.000.000,00 €)\n";
echo "Cents: 12350 => " . GermanFormatter::formatCentsToEuro(12350) . " (erwartet: 123,50 €)\n\n";

// Test Number Formatting
echo "ZAHLENFORMATIERUNG:\n";
echo "-------------------\n";
echo "1234.567 => " . GermanFormatter::formatNumber(1234.567, 3) . " (erwartet: 1.234,567)\n";
echo "0.5 => " . GermanFormatter::formatNumber(0.5, 1) . " (erwartet: 0,5)\n";
echo "Prozent: 75.5 => " . GermanFormatter::formatPercentage(75.5) . " (erwartet: 75,5 %)\n\n";

// Test Date/Time Formatting
echo "DATUMS-/ZEITFORMATIERUNG:\n";
echo "-------------------------\n";
$now = Carbon::now();
echo "Jetzt: " . GermanFormatter::formatDateTime($now) . "\n";
echo "Nur Datum: " . GermanFormatter::formatDate($now) . "\n";
echo "Nur Zeit: " . GermanFormatter::formatTime($now) . "\n";
echo "Mit Sekunden: " . GermanFormatter::formatDateTime($now, true, true) . "\n";
echo "Relativ: " . GermanFormatter::formatRelativeTime($now->subMinutes(5)) . "\n\n";

// Test Phone Number Formatting
echo "TELEFONNUMMERNFORMATIERUNG:\n";
echo "---------------------------\n";
echo "+491234567890 => " . GermanFormatter::formatPhoneNumber('+491234567890') . "\n";
echo "00491234567890 => " . GermanFormatter::formatPhoneNumber('00491234567890') . "\n";
echo "01234567890 => " . GermanFormatter::formatPhoneNumber('01234567890') . "\n";
echo "030123456 => " . GermanFormatter::formatPhoneNumber('030123456') . "\n\n";

// Test Duration Formatting
echo "DAUERFORMATIERUNG:\n";
echo "------------------\n";
echo "45 Sek => " . GermanFormatter::formatDuration(45) . " (erwartet: 45 Sek.)\n";
echo "90 Sek => " . GermanFormatter::formatDuration(90) . " (erwartet: 1 Min. 30 Sek.)\n";
echo "300 Sek => " . GermanFormatter::formatDuration(300) . " (erwartet: 5 Min.)\n";
echo "3665 Sek => " . GermanFormatter::formatDuration(3665) . " (erwartet: 1 Std. 1 Min. 5 Sek.)\n\n";

// Test Status Formatting
echo "STATUS-FORMATIERUNG:\n";
echo "--------------------\n";
$statuses = ['success', 'failed', 'pending', 'in_progress', 'completed'];
foreach ($statuses as $status) {
    $formatted = GermanFormatter::formatStatus($status);
    echo "$status => " . $formatted['text'] . " (Farbe: " . $formatted['color'] . ")\n";
}
echo "\n";

// Test Sentiment Formatting
echo "SENTIMENT-FORMATIERUNG:\n";
echo "-----------------------\n";
$sentiments = ['positive', 'negative', 'neutral', 'mixed'];
foreach ($sentiments as $sentiment) {
    $formatted = GermanFormatter::formatSentiment($sentiment);
    echo "$sentiment => " . $formatted['icon'] . " " . $formatted['text'] . "\n";
}
echo "\n";

// Test Urgency Formatting
echo "DRINGLICHKEITS-FORMATIERUNG:\n";
echo "----------------------------\n";
$urgencies = ['urgent', 'dringend', 'high', 'normal', 'low'];
foreach ($urgencies as $urgency) {
    $formatted = GermanFormatter::formatUrgency($urgency);
    echo "$urgency => " . $formatted['icon'] . " " . $formatted['text'] . " (Farbe: " . $formatted['color'] . ")\n";
}
echo "\n";

// Test Boolean Formatting
echo "BOOLEAN-FORMATIERUNG:\n";
echo "---------------------\n";
echo "true => " . GermanFormatter::formatBoolean(true) . " (erwartet: Ja)\n";
echo "false => " . GermanFormatter::formatBoolean(false) . " (erwartet: Nein)\n";
echo "true (custom) => " . GermanFormatter::formatBoolean(true, 'Aktiv', 'Inaktiv') . " (erwartet: Aktiv)\n\n";

// Test File Size Formatting
echo "DATEIGRÖSSEN-FORMATIERUNG:\n";
echo "--------------------------\n";
echo "1024 B => " . GermanFormatter::formatFileSize(1024) . " (erwartet: 1,00 KB)\n";
echo "1048576 B => " . GermanFormatter::formatFileSize(1048576) . " (erwartet: 1,00 MB)\n";
echo "1572864 B => " . GermanFormatter::formatFileSize(1572864) . " (erwartet: 1,50 MB)\n\n";

echo "=====================================\n";
echo "✅ ALLE TESTS ABGESCHLOSSEN\n";
echo "=====================================\n";