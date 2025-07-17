#!/usr/bin/env php
<?php

echo "Fixing PHPUnit 11 deprecations...\n";

$testFiles = glob(__DIR__ . '/tests/**/*Test.php', GLOB_BRACE);
$fixed = 0;
$errors = 0;

foreach ($testFiles as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Check if already has #[Test] attributes
    if (strpos($content, '#[Test]') !== false) {
        echo "✓ Already fixed: " . basename($file) . "\n";
        continue;
    }
    
    // Check if has @test annotations
    if (strpos($content, '@test') === false) {
        echo "- No @test found: " . basename($file) . "\n";
        continue;
    }
    
    // Add use statement if not present
    if (strpos($content, 'use PHPUnit\\Framework\\Attributes\\Test;') === false) {
        // Find the right place to add use statement (after namespace)
        $pattern = '/(namespace [^;]+;)/';
        if (preg_match($pattern, $content, $matches)) {
            $replacement = $matches[1] . "\n\nuse PHPUnit\\Framework\\Attributes\\Test;";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
    }
    
    // Replace @test with #[Test]
    $content = preg_replace('/\s*\*\s*@test\s*$/m', '', $content);
    
    // Add #[Test] attribute before public function
    $pattern = '/(\/\*\*[^\/]*\*\/\s*)(public function test)/m';
    $content = preg_replace($pattern, '$1#[Test]' . "\n    " . '$2', $content);
    
    // Also handle methods that start with 'it_' or other patterns
    $pattern = '/(\/\*\*[^\/]*\*\/\s*)(public function (?:it_|should_|can_|will_))/m';
    $content = preg_replace($pattern, '$1#[Test]' . "\n    " . '$2', $content);
    
    // Clean up double line breaks
    $content = preg_replace('/\n\n\n+/', "\n\n", $content);
    
    if ($content !== $originalContent) {
        if (file_put_contents($file, $content)) {
            echo "✅ Fixed: " . basename($file) . "\n";
            $fixed++;
        } else {
            echo "❌ Error writing: " . basename($file) . "\n";
            $errors++;
        }
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "Total files: " . count($testFiles) . "\n";
echo "Fixed: $fixed\n";
echo "Errors: $errors\n";
echo "Already fixed: " . (count($testFiles) - $fixed - $errors) . "\n";
echo "========================================\n";