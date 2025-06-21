#!/usr/bin/env php
<?php

/**
 * Script to fix duplicate table creation in migrations
 * This will update all migrations that create tables multiple times
 * to use the CompatibleMigration base class
 */

$duplicateTables = [
    'agents',
    'api_call_logs',
    'branch_service',
    'branch_service_overrides',
    'business_hours_templates',
    'calendar_event_types',
    'dashboard_widget_settings',
    'event_type_import_logs',
    'integrations',
    'master_services',
    'onboarding_progress',
    'password_reset_tokens',
    'phone_numbers',
    'retell_agents',
    'retell_webhooks',
    'service_staff',
    'sessions',
    'staff',
    'staff_branches',
    'staff_service_assignments',
    'staff_services',
    'unified_event_types',
    'validation_results',
];

$migrationsPath = __DIR__ . '/database/migrations';
$migrationsToFix = [];

// Find all migrations that create duplicate tables
foreach ($duplicateTables as $tableName) {
    $pattern = $migrationsPath . '/*_create_' . $tableName . '_table.php';
    $files = glob($pattern);
    
    if (count($files) > 1) {
        // Keep the oldest one, mark others for fixing
        sort($files);
        for ($i = 1; $i < count($files); $i++) {
            $migrationsToFix[] = [
                'file' => $files[$i],
                'table' => $tableName,
                'is_duplicate' => true,
            ];
        }
    }
}

// Also find migrations that might create tables without the standard naming
$allMigrations = glob($migrationsPath . '/*.php');
foreach ($allMigrations as $migration) {
    $content = file_get_contents($migration);
    
    foreach ($duplicateTables as $tableName) {
        if (strpos($content, "Schema::create('$tableName'") !== false &&
            !in_array($migration, array_column($migrationsToFix, 'file')) &&
            strpos($migration, "_create_{$tableName}_table.php") === false) {
            $migrationsToFix[] = [
                'file' => $migration,
                'table' => $tableName,
                'is_duplicate' => true,
            ];
        }
    }
}

echo "Found " . count($migrationsToFix) . " migrations to fix:\n\n";

foreach ($migrationsToFix as $migration) {
    echo "- " . basename($migration['file']) . " (table: " . $migration['table'] . ")\n";
}

echo "\nFixing migrations...\n\n";

$fixed = 0;
$failed = 0;

foreach ($migrationsToFix as $migration) {
    $file = $migration['file'];
    $content = file_get_contents($file);
    
    // Check if already using CompatibleMigration
    if (strpos($content, 'CompatibleMigration') !== false) {
        echo "✓ " . basename($file) . " already uses CompatibleMigration\n";
        continue;
    }
    
    // Replace Migration with CompatibleMigration
    $newContent = str_replace(
        'use Illuminate\Database\Migrations\Migration;',
        'use App\Database\CompatibleMigration;',
        $content
    );
    
    // If no explicit Migration import, add CompatibleMigration import
    if ($newContent === $content && strpos($content, 'extends Migration') !== false) {
        $newContent = preg_replace(
            '/^<\?php\s*\n/m',
            "<?php\n\nuse App\Database\CompatibleMigration;\n",
            $content
        );
    }
    
    // Replace extends Migration with extends CompatibleMigration
    $newContent = str_replace(
        'extends Migration',
        'extends CompatibleMigration',
        $newContent
    );
    
    // Replace Schema::create with createTableIfNotExists
    $tableName = $migration['table'];
    $newContent = preg_replace(
        "/Schema::create\('$tableName',/",
        "\$this->createTableIfNotExists('$tableName',",
        $newContent
    );
    
    // Replace Schema::dropIfExists with dropTableIfExists
    $newContent = preg_replace(
        "/Schema::dropIfExists\('$tableName'\)/",
        "\$this->dropTableIfExists('$tableName')",
        $newContent
    );
    
    // Replace json columns
    $newContent = preg_replace_callback(
        '/\$table->json\(([\'"])([^\'"]+)\1\)/',
        function ($matches) {
            return '$this->addJsonColumn($table, ' . $matches[1] . $matches[2] . $matches[1] . ', true)';
        },
        $newContent
    );
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "✓ Fixed " . basename($file) . "\n";
        $fixed++;
    } else {
        echo "✗ Could not fix " . basename($file) . " - manual intervention needed\n";
        $failed++;
    }
}

echo "\n\nSummary:\n";
echo "- Fixed: $fixed migrations\n";
echo "- Failed: $failed migrations\n";

if ($failed > 0) {
    echo "\nManual intervention needed for failed migrations.\n";
    echo "Make sure they:\n";
    echo "1. Extend CompatibleMigration instead of Migration\n";
    echo "2. Use createTableIfNotExists() instead of Schema::create()\n";
    echo "3. Use dropTableIfExists() instead of Schema::dropIfExists()\n";
    echo "4. Use addJsonColumn() for JSON columns\n";
}

echo "\n✅ Done!\n";