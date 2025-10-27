<?php
// Check OPCache status from FPM perspective
header('Content-Type: application/json');

$status = opcache_get_status();
$config = opcache_get_configuration();

echo json_encode([
    'opcache_enabled' => $status['opcache_enabled'] ?? false,
    'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
    'hits' => $status['opcache_statistics']['hits'] ?? 0,
    'misses' => $status['opcache_statistics']['misses'] ?? 0,
    'memory_usage' => $status['memory_usage'] ?? null,
    'interned_strings_usage' => $status['interned_strings_usage'] ?? null,
    'directives' => [
        'enable' => $config['directives']['opcache.enable'] ?? null,
        'enable_cli' => $config['directives']['opcache.enable_cli'] ?? null,
        'memory_consumption' => $config['directives']['opcache.memory_consumption'] ?? null,
        'max_accelerated_files' => $config['directives']['opcache.max_accelerated_files'] ?? null,
        'revalidate_freq' => $config['directives']['opcache.revalidate_freq'] ?? null,
    ],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
