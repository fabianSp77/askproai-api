<?php
session_start();
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

if (\!auth()->check()) {
    header("Location: /admin/login");
    exit;
}
?>
<\!DOCTYPE html>
<html>
<head>
    <title>Test Livewire Loading</title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Livewire Loading Test</h1>
    
    <div class="test-section">
        <h2>1. Check Livewire Scripts</h2>
        <div id="livewire-check"></div>
    </div>
    
    <div class="test-section">
        <h2>2. Network Requests</h2>
        <div id="network-log"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Links to Test</h2>
        <a href="/admin/calls" target="_blank">Open /admin/calls</a><br>
        <a href="/admin/simple-calls" target="_blank">Open /admin/simple-calls (no Livewire)</a><br>
        <a href="/debug-ajax-requests.html" target="_blank">Open AJAX Debug Tool</a>
    </div>

    <script>
        // Check if Livewire is loaded
        document.getElementById("livewire-check").innerHTML = 
            window.Livewire ? "<span class=\"success\">✓ Livewire is loaded</span>" : 
            "<span class=\"error\">✗ Livewire is NOT loaded</span>";
    </script>
</body>
</html>
