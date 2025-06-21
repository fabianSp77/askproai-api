<?php
// Simple Redis test without Laravel
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Test without password
echo "Testing Redis without password...\n";
try {
    $redis->ping();
    echo "✅ Redis connected successfully without password\n";
    
    // Test set/get
    $redis->set('test_key', 'test_value');
    $value = $redis->get('test_key');
    echo "✅ Set/Get test passed: $value\n";
    
    // Clean up
    $redis->del('test_key');
} catch (Exception $e) {
    echo "❌ Redis error: " . $e->getMessage() . "\n";
}