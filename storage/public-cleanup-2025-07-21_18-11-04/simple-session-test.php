<?php
session_start();

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'set') {
        $_SESSION['test_key'] = 'test_value_' . time();
        $_SESSION['portal_user_id'] = 41;
        echo json_encode([
            'action' => 'set',
            'session_id' => session_id(),
            'data_set' => [
                'test_key' => $_SESSION['test_key'],
                'portal_user_id' => $_SESSION['portal_user_id']
            ]
        ]);
        exit;
    }
    
    if ($_GET['action'] === 'get') {
        echo json_encode([
            'action' => 'get',
            'session_id' => session_id(),
            'data_retrieved' => [
                'test_key' => $_SESSION['test_key'] ?? 'NOT FOUND',
                'portal_user_id' => $_SESSION['portal_user_id'] ?? 'NOT FOUND'
            ]
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple PHP Session Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        button { padding: 10px 20px; margin: 5px; }
        pre { background: #f0f0f0; padding: 10px; }
    </style>
</head>
<body>
    <h1>Simple PHP Session Test</h1>
    <p>This tests basic PHP sessions without Laravel.</p>
    
    <button onclick="setSession()">1. Set Session Data</button>
    <button onclick="getSession()">2. Get Session Data</button>
    
    <h2>Current Session:</h2>
    <pre><?php
    echo "Session ID: " . session_id() . "\n";
    echo "Session Data:\n";
    print_r($_SESSION);
    ?></pre>
    
    <h2>Test Results:</h2>
    <pre id="results">Click buttons to test...</pre>
    
    <script>
    async function setSession() {
        const response = await fetch('?action=set');
        const data = await response.json();
        document.getElementById('results').textContent = 'SET Result:\n' + JSON.stringify(data, null, 2);
    }
    
    async function getSession() {
        const response = await fetch('?action=get');
        const data = await response.json();
        document.getElementById('results').textContent += '\n\nGET Result:\n' + JSON.stringify(data, null, 2);
    }
    </script>
</body>
</html>