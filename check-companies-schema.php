<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking companies table schema...\n\n";

// Get column info
$columns = DB::select("SHOW COLUMNS FROM companies");

echo "ID column details:\n";
foreach ($columns as $column) {
    if ($column->Field === 'id') {
        print_r($column);
        break;
    }
}

echo "\nAll columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}