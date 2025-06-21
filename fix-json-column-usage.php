#!/usr/bin/env php
<?php

/**
 * Fix incorrect addJsonColumn usage in migrations
 * Remove ->nullable() chaining since addJsonColumn already handles it
 */

$migrationsPath = __DIR__ . '/database/migrations';
$files = glob($migrationsPath . '/*.php');
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix patterns like: $this->addJsonColumn($table, 'field', true)->nullable();
    // The third parameter true already makes it nullable
    $content = preg_replace(
        '/(\$this->addJsonColumn\([^)]+,\s*true\))->nullable\(\)/',
        '$1',
        $content
    );
    
    // Also fix patterns with comments
    $content = preg_replace(
        '/(\$this->addJsonColumn\([^)]+,\s*true\))->nullable\(\)->comment\(([^)]+)\)/',
        '$1->comment($2)',
        $content
    );
    
    // Fix patterns with default values
    $content = preg_replace(
        '/(\$this->addJsonColumn\([^)]+,\s*true\))->nullable\(\)->default\(([^)]+)\)/',
        '$1->default($2)',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✓ Fixed " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\n✅ Fixed $fixed migration files\n";