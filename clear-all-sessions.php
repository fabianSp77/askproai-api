<?php
// Alle Sessions und Cookies löschen

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Clearing All Sessions ===\n\n";

// Alle Sessions aus der Datenbank löschen
if (config('session.driver') === 'database') {
    DB::table('sessions')->truncate();
    echo "✓ Database sessions cleared\n";
}

// Session-Dateien löschen
$sessionPath = storage_path('framework/sessions');
$files = glob($sessionPath . '/*');
foreach ($files as $file) {
    if (is_file($file) && !str_contains($file, '.gitignore')) {
        unlink($file);
    }
}
echo "✓ File sessions cleared\n";

// Cache leeren
exec('php artisan cache:clear');
echo "✓ Cache cleared\n";

echo "\nDone! All sessions have been cleared.\n";