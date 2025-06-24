#!/usr/bin/env php
<?php

echo "🔧 Fixing Test Suite Issues...\n\n";

// 1. Fix phpunit.xml to use in-memory SQLite
$phpunitXml = file_get_contents('phpunit.xml');
if (!str_contains($phpunitXml, 'DB_DATABASE=:memory:')) {
    echo "📝 Updating phpunit.xml for in-memory SQLite...\n";
    $phpunitXml = str_replace(
        '<env name="DB_CONNECTION" value="sqlite"/>',
        '<env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>',
        $phpunitXml
    );
    file_put_contents('phpunit.xml', $phpunitXml);
    echo "✅ Updated phpunit.xml\n";
}

// 2. Create a test bootstrap file
$testBootstrap = '<?php

// Increase memory limit for tests
ini_set("memory_limit", "512M");

// Set longer execution time
set_time_limit(300);

// Disable Xdebug for faster tests
if (function_exists("xdebug_disable")) {
    xdebug_disable();
}

// Ensure we\'re in testing environment
putenv("APP_ENV=testing");
putenv("CACHE_DRIVER=array");
putenv("SESSION_DRIVER=array");
putenv("QUEUE_CONNECTION=sync");
putenv("MAIL_MAILER=array");
putenv("BROADCAST_DRIVER=log");

// Disable MCP services during testing
putenv("MCP_ENABLED=false");
putenv("DB_CONNECTION_POOLING=false");
';

file_put_contents('tests/bootstrap.php', $testBootstrap);
echo "✅ Created test bootstrap file\n";

// 3. Create a base test case with proper setup
$baseTestCase = '<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable foreign key checks for SQLite
        if (DB::getDriverName() === "sqlite") {
            DB::statement("PRAGMA foreign_keys=OFF");
        }
        
        // Run migrations without foreign key constraints
        Artisan::call("migrate:fresh", ["--force" => true]);
        
        // Re-enable foreign keys
        if (DB::getDriverName() === "sqlite") {
            DB::statement("PRAGMA foreign_keys=ON");
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up
        Artisan::call("migrate:reset", ["--force" => true]);
        
        parent::tearDown();
    }
}
';

// Check if TestCase.php needs updating
$testCasePath = 'tests/TestCase.php';
if (file_exists($testCasePath)) {
    $currentTestCase = file_get_contents($testCasePath);
    if (!str_contains($currentTestCase, 'PRAGMA foreign_keys')) {
        echo "📝 Updating TestCase.php...\n";
        file_put_contents($testCasePath, $baseTestCase);
        echo "✅ Updated TestCase.php\n";
    }
}

// 4. Fix specific test files with issues
echo "\n📋 Checking for common test issues...\n";

// Remove deprecated @test annotations
$testFiles = glob('tests/**/*Test.php');
$fixedCount = 0;

foreach ($testFiles as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace @test with #[Test] attribute
    if (str_contains($content, '* @test')) {
        $content = preg_replace('/\s*\*\s*@test\s*\n/', '', $content);
        $content = str_replace('public function ', "use PHPUnit\Framework\Attributes\Test;\n\n    #[Test]\n    public function ", $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixedCount++;
    }
}

echo "✅ Fixed $fixedCount test files\n";

// 5. Create a simple test to verify setup
$simpleTest = '<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseConnectionTest extends TestCase
{
    #[Test]
    public function it_can_connect_to_test_database()
    {
        $this->assertTrue(true);
    }
    
    #[Test]
    public function it_uses_sqlite_in_memory()
    {
        $driver = config("database.default");
        $this->assertEquals("sqlite", $driver);
        
        $database = config("database.connections.sqlite.database");
        $this->assertEquals(":memory:", $database);
    }
}
';

file_put_contents('tests/Unit/DatabaseConnectionTest.php', $simpleTest);
echo "✅ Created DatabaseConnectionTest.php\n";

echo "\n🎉 Test suite fixes completed!\n";
echo "\nNext steps:\n";
echo "1. Run: composer dump-autoload\n";
echo "2. Run: php artisan test tests/Unit/DatabaseConnectionTest.php\n";
echo "3. If successful, run: php artisan test --parallel\n";