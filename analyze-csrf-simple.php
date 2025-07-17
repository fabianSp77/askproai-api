<?php
/**
 * Einfache CSRF Analyse
 */

echo "\n=== CSRF/Page Expired Analyse ===\n\n";

// 1. Session Pfade prüfen
echo "1. Session-Verzeichnisse:\n";
$paths = [
    'default' => __DIR__ . '/storage/framework/sessions',
    'admin' => __DIR__ . '/storage/framework/sessions/admin',
    'portal' => __DIR__ . '/storage/framework/sessions/portal',
];

foreach ($paths as $type => $path) {
    if (is_dir($path)) {
        $count = count(glob($path . '/*'));
        $writable = is_writable($path) ? 'writable' : 'NOT writable';
        echo "   ✓ $type: $path ($writable, $count files)\n";
    } else {
        echo "   ✗ $type: $path (not exists)\n";
        // Try to create
        if (@mkdir($path, 0755, true)) {
            echo "     → Created successfully\n";
        }
    }
}

// 2. Konfigurationsdateien prüfen
echo "\n2. Session-Konfigurationsdateien:\n";
$configs = [
    'session.php' => __DIR__ . '/config/session.php',
    'session_admin.php' => __DIR__ . '/config/session_admin.php',
    'session_portal.php' => __DIR__ . '/config/session_portal.php',
];

foreach ($configs as $name => $path) {
    if (file_exists($path)) {
        echo "   ✓ $name exists\n";
        
        // Read cookie names
        $content = file_get_contents($path);
        if (preg_match("/'cookie'\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            echo "     Cookie: {$matches[1]}\n";
        }
        if (preg_match("/'path'\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            echo "     Path: {$matches[1]}\n";
        }
    } else {
        echo "   ✗ $name missing\n";
    }
}

// 3. Middleware-Dateien prüfen
echo "\n3. CSRF-relevante Middleware:\n";
$middlewares = [
    'VerifyCsrfToken.php' => __DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php',
    'DisableFilamentCSRF.php' => __DIR__ . '/app/Http/Middleware/DisableFilamentCSRF.php',
    'FixLivewireCSRF.php' => __DIR__ . '/app/Http/Middleware/FixLivewireCSRF.php',
    'FixAdminSession.php' => __DIR__ . '/app/Http/Middleware/FixAdminSession.php',
    'AdminSessionConfig.php' => __DIR__ . '/app/Http/Middleware/AdminSessionConfig.php',
    'PortalSessionIsolation.php' => __DIR__ . '/app/Http/Middleware/PortalSessionIsolation.php',
];

foreach ($middlewares as $name => $path) {
    if (file_exists($path)) {
        echo "   ✓ $name exists\n";
        
        // Check if it's referenced in Kernel
        $kernelContent = file_get_contents(__DIR__ . '/app/Http/Kernel.php');
        $className = basename($path, '.php');
        if (strpos($kernelContent, $className) !== false) {
            if (strpos($kernelContent, "// TEMPORARILY DISABLED") !== false && strpos($kernelContent, "PortalSessionIsolation") !== false) {
                echo "     ⚠ PortalSessionIsolation is DISABLED!\n";
            } else {
                echo "     → Used in Kernel\n";
            }
        }
    } else {
        echo "   ✗ $name missing\n";
    }
}

// 4. CSRF Exclusions prüfen
echo "\n4. CSRF Exclusions in VerifyCsrfToken:\n";
$csrfFile = __DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php';
if (file_exists($csrfFile)) {
    $content = file_get_contents($csrfFile);
    if (preg_match('/protected\s+\$except\s*=\s*\[(.*?)\];/s', $content, $matches)) {
        $exceptions = $matches[1];
        if (strpos($exceptions, "'admin/*'") !== false || strpos($exceptions, '"admin/*"') !== false) {
            echo "   ✓ admin/* is excluded\n";
        }
        if (strpos($exceptions, "'livewire/*'") !== false || strpos($exceptions, '"livewire/*"') !== false) {
            echo "   ✓ livewire/* is excluded\n";
        }
    }
}

// 5. Problem-Diagnose
echo "\n5. Mögliche Probleme:\n";
$problems = [];

// Check if PortalSessionIsolation is disabled
$kernelContent = file_get_contents(__DIR__ . '/app/Http/Kernel.php');
if (strpos($kernelContent, "// TEMPORARILY DISABLED: \App\Http\Middleware\PortalSessionIsolation::class") !== false) {
    $problems[] = "PortalSessionIsolation ist deaktiviert - dies kann zu Session-Konflikten führen";
}

// Check session paths
if (!is_dir($paths['admin'])) {
    $problems[] = "Admin session directory fehlt";
}

// Check middleware order
if (strpos($kernelContent, "DisableFilamentCSRF") !== false) {
    $problems[] = "DisableFilamentCSRF regeneriert bei jedem Request das Token";
}

if (empty($problems)) {
    echo "   ✓ Keine offensichtlichen Probleme gefunden\n";
} else {
    foreach ($problems as $problem) {
        echo "   ⚠ $problem\n";
    }
}

// 6. Lösungsvorschläge
echo "\n6. Lösungsvorschläge:\n";
echo "   1. PortalSessionIsolation reaktivieren (entfernen Sie 'TEMPORARILY DISABLED')\n";
echo "   2. DisableFilamentCSRF anpassen - regenerateToken() nur wenn nötig\n";
echo "   3. Session-Cache leeren: rm -rf storage/framework/sessions/*\n";
echo "   4. Config-Cache neu generieren: php artisan config:clear && php artisan config:cache\n";
echo "   5. Cookies im Browser löschen und neu einloggen\n";

echo "\n";