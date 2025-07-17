<?php
// Simple cache clearing script
header('Content-Type: application/json');

try {
    exec('cd /var/www/api-gateway && php artisan optimize:clear 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo json_encode(['status' => 'success', 'message' => 'All caches cleared']);
    } else {
        echo json_encode(['status' => 'error', 'message' => implode("\n", $output)]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}