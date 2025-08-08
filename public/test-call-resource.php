<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Filament\Admin\Resources\CallResource;

echo "<h1>CallResource Debug Test</h1>";
echo "<pre>";

// Check file modification time
$file = '/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php';
echo "File: $file\n";
echo "Last modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
echo "File size: " . filesize($file) . " bytes\n\n";

// Check class reflection
$reflection = new ReflectionClass(CallResource::class);
echo "Class loaded from: " . $reflection->getFileName() . "\n\n";

// Check table method
$tableMethod = $reflection->getMethod('table');
echo "Table method exists: YES\n";
echo "Table method line: " . $tableMethod->getStartLine() . "\n\n";

// Get first 20 lines of table method
$file_content = file($file);
echo "Table method content (first 20 lines):\n";
echo "=====================================\n";
for ($i = $tableMethod->getStartLine() - 1; $i < min($tableMethod->getStartLine() + 20, count($file_content)); $i++) {
    echo ($i + 1) . ": " . $file_content[$i];
}

echo "\n\nColumn definitions found:\n";
echo "========================\n";
$tableContent = implode('', array_slice($file_content, $tableMethod->getStartLine() - 1, 50));
preg_match_all('/TextColumn::make\([\'"]([^\'"]+)[\'"]\)/', $tableContent, $matches);
foreach ($matches[1] as $column) {
    echo "- $column\n";
}

// Check OPcache status for this file
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(true);
    echo "\n\nOPcache Status:\n";
    echo "===============\n";
    if (isset($status['scripts'][$file])) {
        echo "File is cached in OPcache\n";
        echo "Last used: " . date('Y-m-d H:i:s', $status['scripts'][$file]['last_used_timestamp'] ?? time()) . "\n";
    } else {
        echo "File is NOT in OPcache\n";
    }
}

echo "</pre>";

// Add cache buster
echo "<hr>";
echo "<h2>Test Links (with cache buster)</h2>";
$time = time();
echo "<ul>";
echo "<li><a href='/admin/calls?_t=$time' target='_blank'>Filament Calls Page (cache bust)</a></li>";
echo "<li><a href='/calls-table.php?_t=$time' target='_blank'>Alternative Calls Table</a></li>";
echo "</ul>";

echo "<h2>Actions to force reload:</h2>";
echo "<ol>";
echo "<li>Press <strong>Ctrl+F5</strong> on the Calls page</li>";
echo "<li>Or open in Incognito/Private mode</li>";
echo "<li>Or visit <a href='/force-reload.html'>Force Reload Instructions</a></li>";
echo "</ol>";