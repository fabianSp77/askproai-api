<?php

// Stelle sicher, dass alle 4 Dashboards korrekt sichtbar sind

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

echo "=== DASHBOARD SICHTBARKEIT KORRIGIEREN ===\n\n";

// SimpleDashboard sollte keine shouldRegisterNavigation haben (damit es sichtbar ist)
$simpleDashFile = $pagesPath . '/SimpleDashboard.php';
if (file_exists($simpleDashFile)) {
    $content = file_get_contents($simpleDashFile);
    
    // Entferne shouldRegisterNavigation falls vorhanden
    if (preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}\s*/s',
            '',
            $content
        );
        file_put_contents($simpleDashFile, $content);
        echo "✅ SimpleDashboard: shouldRegisterNavigation entfernt (jetzt sichtbar)\n";
    } else {
        echo "✅ SimpleDashboard: bereits sichtbar\n";
    }
}

// SystemMonitoringDashboard sollte immer sichtbar sein (nur Zugriff über canAccess steuern)
$systemMonFile = $pagesPath . '/SystemMonitoringDashboard.php';
if (file_exists($systemMonFile)) {
    $content = file_get_contents($systemMonFile);
    
    // Entferne shouldRegisterNavigation oder mache es true
    if (preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return true; // Immer im Menü zeigen, Zugriff wird über canAccess() gesteuert
    }',
            $content
        );
        file_put_contents($systemMonFile, $content);
        echo "✅ SystemMonitoringDashboard: shouldRegisterNavigation = true (im Menü sichtbar)\n";
    } else {
        echo "✅ SystemMonitoringDashboard: bereits sichtbar\n";
    }
}

// AICallCenter sollte sichtbar sein
$aiCallFile = $pagesPath . '/AICallCenter.php';
if (file_exists($aiCallFile)) {
    $content = file_get_contents($aiCallFile);
    
    if (preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return true;
    }',
            $content
        );
        file_put_contents($aiCallFile, $content);
        echo "✅ AICallCenter: shouldRegisterNavigation = true\n";
    } else {
        echo "✅ AICallCenter: bereits sichtbar\n";
    }
}

// EventAnalyticsDashboard sollte sichtbar sein
$analyticsFile = $pagesPath . '/EventAnalyticsDashboard.php';
if (file_exists($analyticsFile)) {
    $content = file_get_contents($analyticsFile);
    
    if (preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return true;
    }',
            $content
        );
        file_put_contents($analyticsFile, $content);
        echo "✅ EventAnalyticsDashboard: shouldRegisterNavigation = true\n";
    } else {
        echo "✅ EventAnalyticsDashboard: bereits sichtbar\n";
    }
}

echo "\n📊 Erwartete Dashboard-Struktur:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Übersicht (SimpleDashboard) - Hauptdashboard\n";
echo "2. Analytics & Trends (EventAnalyticsDashboard) - Business Intelligence\n";
echo "3. AI Operations (AICallCenter) - Call Management\n";
echo "4. System Monitor (SystemMonitoringDashboard) - Technische Überwachung\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";