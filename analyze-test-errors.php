<?php

echo "\n🔍 Test Error Analysis\n";
echo "======================\n\n";

// Analyze error patterns
$errorPatterns = [
    'mock_missing' => 0,
    'database_migration' => 0,
    'class_not_found' => 0,
    'method_missing' => 0,
    'syntax_error' => 0,
    'other' => 0
];

$testDirs = [
    'tests/Unit/Models',
    'tests/Integration/Services',
    'tests/Feature/Webhook',
    'tests/E2E'
];

echo "Analyzing error patterns...\n\n";

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    echo "📁 $dir:\n";
    
    // Run tests and capture output
    $output = shell_exec("./vendor/bin/phpunit $dir --no-coverage 2>&1");
    
    // Analyze errors
    if (strpos($output, 'Class') !== false && strpos($output, 'not found') !== false) {
        $errorPatterns['class_not_found']++;
        echo "   ❌ Class not found errors\n";
    }
    
    if (strpos($output, 'Call to undefined method') !== false) {
        $errorPatterns['method_missing']++;
        echo "   ❌ Missing method errors\n";
    }
    
    if (strpos($output, 'ParseError') !== false || strpos($output, 'syntax error') !== false) {
        $errorPatterns['syntax_error']++;
        echo "   ❌ Syntax errors\n";
    }
    
    if (strpos($output, 'Migration') !== false || strpos($output, 'SQLSTATE') !== false) {
        $errorPatterns['database_migration']++;
        echo "   ❌ Database/Migration errors\n";
    }
    
    if (strpos($output, 'Mock') !== false || strpos($output, 'Mockery') !== false) {
        $errorPatterns['mock_missing']++;
        echo "   ❌ Mock setup errors\n";
    }
    
    // Extract specific errors
    preg_match_all('/Error: (.+)/', $output, $matches);
    if (!empty($matches[1])) {
        echo "   Specific errors:\n";
        $uniqueErrors = array_unique($matches[1]);
        foreach (array_slice($uniqueErrors, 0, 3) as $error) {
            echo "   - " . substr($error, 0, 80) . "...\n";
        }
    }
    
    echo "\n";
}

echo "\n📊 Error Pattern Summary:\n";
foreach ($errorPatterns as $pattern => $count) {
    if ($count > 0) {
        echo "   • " . str_replace('_', ' ', ucfirst($pattern)) . ": $count\n";
    }
}

// Suggest fixes
echo "\n💡 Suggested Fixes:\n\n";

echo "1. **Database/Migration Errors**:\n";
echo "   ```bash\n";
echo "   # Ensure test database is migrated\n";
echo "   php artisan migrate --env=testing\n";
echo "   ```\n\n";

echo "2. **Missing Mock Services**:\n";
echo "   Create `tests/TestCase.php` with:\n";
echo "   ```php\n";
echo "   protected function setUp(): void {\n";
echo "       parent::setUp();\n";
echo "       \$this->mockExternalServices();\n";
echo "   }\n";
echo "   ```\n\n";

echo "3. **Class Not Found**:\n";
echo "   ```bash\n";
echo "   composer dump-autoload\n";
echo "   php artisan clear-compiled\n";
echo "   ```\n\n";

// Quick fix script
$quickFix = <<<'PHP'
<?php
// Quick fix for common test issues

// 1. Run migrations for test database
echo "Running test migrations...\n";
exec('php artisan migrate --env=testing --force');

// 2. Clear caches
echo "Clearing caches...\n";
exec('php artisan cache:clear --env=testing');
exec('php artisan config:clear --env=testing');

// 3. Regenerate autoload
echo "Regenerating autoload...\n";
exec('composer dump-autoload');

echo "✅ Quick fixes applied!\n";
PHP;

file_put_contents('quick-fix-tests.php', $quickFix);
echo "Created: quick-fix-tests.php\n";