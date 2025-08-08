#!/usr/bin/env php
<?php

/**
 * Security Fix Script
 * Fixes XSS vulnerabilities and other security issues
 */

echo "🔒 Starting Security Fixes...\n\n";

$basePath = '/var/www/api-gateway/app/Filament/Admin/Resources/';
$issuesFixed = 0;

// Fix 1: CompanyResource.php - Escape all dynamic content in HtmlString
$file = $basePath . 'CompanyResource.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix Event-ID output (line 347)
    $content = str_replace(
        "'<p>🆔 Event-ID: ' . \$event['id'] . '</p>'",
        "'<p>🆔 Event-ID: ' . htmlspecialchars(\$event['id']) . '</p>'",
        $content
    );
    
    // Fix Team-ID output (line 349)
    $content = str_replace(
        "'<p>👥 Team-ID: ' . \$event['teamId'] . '</p>'",
        "'<p>👥 Team-ID: ' . htmlspecialchars(\$event['teamId']) . '</p>'",
        $content
    );
    
    // Fix Duration output (line 346) - make sure all dynamic content is escaped
    $content = str_replace(
        "'<p>⏱️ Dauer: ' . (\$event['length'] ?? 'Unbekannt') . ' Minuten</p>'",
        "'<p>⏱️ Dauer: ' . htmlspecialchars(\$event['length'] ?? 'Unbekannt') . ' Minuten</p>'",
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Fixed XSS vulnerabilities in CompanyResource.php\n";
        $issuesFixed++;
    }
}

// Fix 2: CallResource.enterprise.php - Escape dynamic content
$file = $basePath . 'CallResource.enterprise.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Find and fix all unescaped dynamic content in HtmlString constructions
    // This is a pattern to find: . $variable . or . $array['key'] .
    $patterns = [
        '/\. \$([a-zA-Z_]+) \. \'/' => '. htmlspecialchars($${1}) . \'',
        '/\. \$([a-zA-Z_]+)\[\'([^\']+)\'\] \. \'/' => '. htmlspecialchars($${1}[\'${2}\']) . \'',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $count = 0;
        $content = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($count > 0) {
            echo "  - Fixed $count potential XSS vulnerabilities in CallResource.enterprise.php\n";
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Fixed XSS vulnerabilities in CallResource.enterprise.php\n";
        $issuesFixed++;
    }
}

// Fix 3: CompanyPricingResource.php - Escape dynamic content
$file = $basePath . 'CompanyPricingResource.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Add htmlspecialchars to all dynamic content before HtmlString
    $lines = explode("\n", $content);
    $inHtmlConstruction = false;
    $htmlVarName = '';
    
    for ($i = 0; $i < count($lines); $i++) {
        if (strpos($lines[$i], '$html = \'') !== false || strpos($lines[$i], '$html .= \'') !== false) {
            $inHtmlConstruction = true;
        }
        
        if ($inHtmlConstruction && strpos($lines[$i], 'return new \\Illuminate\\Support\\HtmlString($html)') !== false) {
            $inHtmlConstruction = false;
        }
        
        if ($inHtmlConstruction) {
            // Look for unescaped variables
            if (preg_match('/\. \$[a-zA-Z_]+/', $lines[$i]) && !strpos($lines[$i], 'htmlspecialchars')) {
                $lines[$i] = preg_replace(
                    '/\. (\$[a-zA-Z_\[\]\'\"]+) \./',
                    '. htmlspecialchars(${1}) .',
                    $lines[$i]
                );
            }
        }
    }
    
    $newContent = implode("\n", $lines);
    if ($newContent !== $originalContent) {
        file_put_contents($file, $newContent);
        echo "✅ Fixed XSS vulnerabilities in CompanyPricingResource.php\n";
        $issuesFixed++;
    }
}

echo "\n";
echo "====================================\n";
echo "🛡️  Security Fixes Complete!\n";
echo "====================================\n";
echo "Issues fixed: $issuesFixed\n";
echo "\n";
echo "Next steps:\n";
echo "1. Clear cache: php artisan optimize:clear\n";
echo "2. Run tests: php artisan test\n";
echo "3. Review changes: git diff\n";