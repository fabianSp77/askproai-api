<?php
// Simple admin test
require __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "<h1>Admin Portal Test</h1>";
    echo "<p>Laravel Version: " . $app->version() . "</p>";
    echo "<p>PHP Version: " . PHP_VERSION . "</p>";
    echo "<p>Filament: Installed ✓</p>";
    echo "<p><a href=\"/admin\">Try Admin Portal</a></p>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
