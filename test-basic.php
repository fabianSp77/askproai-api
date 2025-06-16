<?php
// Minimal test without Laravel
ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP Test OK\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";