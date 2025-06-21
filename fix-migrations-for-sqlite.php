#!/usr/bin/env php
<?php

$migrationsPath = __DIR__ . '/database/migrations';
$migrations = glob($migrationsPath . '/*.php');

$updated = 0;
$errors = [];

foreach ($migrations as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Skip if already using CompatibleMigration
    if (strpos($content, 'CompatibleMigration') !== false) {
        continue;
    }
    
    // Check if it has json columns
    if (strpos($content, '->json(') === false) {
        continue;
    }
    
    echo "Updating: " . basename($file) . "\n";
    
    // Replace the use statement and class declaration
    $content = str_replace(
        "use Illuminate\Database\Migrations\Migration;",
        "use App\Database\CompatibleMigration;",
        $content
    );
    
    $content = str_replace(
        "extends Migration",
        "extends CompatibleMigration",
        $content
    );
    
    // Replace json column definitions
    // Pattern 1: $table->json('column')->nullable()
    $content = preg_replace_callback(
        '/\$table->json\([\'"]([^\'"]+)[\'"]\)->nullable\(\)/',
        function($matches) {
            return '$this->addJsonColumn($table, \'' . $matches[1] . '\', true)';
        },
        $content
    );
    
    // Pattern 2: $table->json('column')
    $content = preg_replace_callback(
        '/\$table->json\([\'"]([^\'"]+)[\'"]\)(?!->)/',
        function($matches) {
            return '$this->addJsonColumn($table, \'' . $matches[1] . '\', false)';
        },
        $content
    );
    
    // Pattern 3: $table->json('column')->default(...)
    $content = preg_replace_callback(
        '/\$table->json\([\'"]([^\'"]+)[\'"]\)->default\([^)]+\)/',
        function($matches) {
            return '$this->addJsonColumn($table, \'' . $matches[1] . '\', false)';
        },
        $content
    );
    
    if ($content !== $originalContent) {
        if (file_put_contents($file, $content)) {
            $updated++;
        } else {
            $errors[] = "Failed to write: " . basename($file);
        }
    }
}

echo "\n=== Migration Update Summary ===\n";
echo "Updated: $updated migrations\n";
if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// List of migrations that need manual review
$manualReview = [
    '2025_05_01_091735_alter_calls_raw_to_json.php' // This one might use DB::statement
];

echo "\nMigrations that may need manual review:\n";
foreach ($manualReview as $migration) {
    if (file_exists($migrationsPath . '/' . $migration)) {
        echo "  - $migration\n";
    }
}