<?php
// Direct browser test for all pages
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test pages in order
$pages = [
    '/' => 'Homepage',
    '/errors' => 'Error Catalog',
    '/help' => 'Help Center',
    '/admin' => 'Admin Panel'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Pages Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        iframe { width: 100%; height: 600px; border: 2px solid #333; }
        .checks { background: #f5f5f5; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>AskProAI Complete Page Testing</h1>
    
    <?php foreach ($pages as $url => $name): ?>
        <div class="test-section">
            <h2>Testing: <?php echo $name; ?> (<?php echo $url; ?>)</h2>
            
            <?php
            try {
                $request = Illuminate\Http\Request::create($url, 'GET');
                $response = $kernel->handle($request);
                $content = $response->getContent();
                $status = $response->getStatusCode();
                
                echo "<div class='checks'>";
                echo "<p>Status Code: <span class='" . ($status == 200 ? 'success' : 'error') . "'>{$status}</span></p>";
                echo "<p>Content Length: " . number_format(strlen($content)) . " bytes</p>";
                
                // Check for key elements
                if ($url == '/errors') {
                    $checks = [
                        'Alpine.js' => strpos($content, 'x-data') !== false,
                        'Search Box' => strpos($content, 'searchQuery') !== false,
                        'Error Data' => strpos($content, 'RETELL_001') !== false,
                        'Responsive Grid' => strpos($content, 'grid-cols-1') !== false && strpos($content, 'md:grid-cols-2') !== false,
                        'Tailwind CSS' => strpos($content, 'tailwindcss') !== false,
                        'Filter Dropdown' => strpos($content, 'showFilters') !== false,
                        'Pagination' => strpos($content, 'pagination') !== false || strpos($content, 'page=') !== false
                    ];
                    
                    echo "<h4>Feature Checks:</h4>";
                    foreach ($checks as $feature => $present) {
                        echo "<p>$feature: <span class='" . ($present ? 'success' : 'error') . "'>" . ($present ? '✅' : '❌') . "</span></p>";
                    }
                }
                
                if ($url == '/help') {
                    $checks = [
                        'Search Input' => strpos($content, 'search') !== false,
                        'Categories' => strpos($content, 'Erste Schritte') !== false,
                        'Popular Articles' => strpos($content, 'Beliebte Artikel') !== false,
                        'Responsive Layout' => strpos($content, 'lg:grid-cols-3') !== false
                    ];
                    
                    echo "<h4>Feature Checks:</h4>";
                    foreach ($checks as $feature => $present) {
                        echo "<p>$feature: <span class='" . ($present ? 'success' : 'error') . "'>" . ($present ? '✅' : '❌') . "</span></p>";
                    }
                }
                
                echo "</div>";
                
                // Show preview in iframe
                $encodedContent = base64_encode($content);
                echo "<h4>Visual Preview:</h4>";
                echo "<iframe src='data:text/html;base64,{$encodedContent}'></iframe>";
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
    <?php endforeach; ?>
    
    <div class="test-section">
        <h2>JavaScript Interaction Tests</h2>
        <p>Open browser console and run these tests on the actual pages:</p>
        <pre>
// Test Alpine.js on Error Catalog
document.querySelector('[x-data]')?._x_dataStack

// Test search functionality
document.querySelector('input[x-model="searchQuery"]')?.value = 'database'
document.querySelector('input[x-model="searchQuery"]')?.dispatchEvent(new Event('input'))

// Test filter dropdowns
document.querySelector('[x-show="showFilters"]')

// Test responsive menu
window.dispatchEvent(new Event('resize'))
        </pre>
    </div>
    
    <div class="test-section">
        <h2>Mobile Responsiveness Test</h2>
        <p>To test mobile view:</p>
        <ol>
            <li>Open Chrome DevTools (F12)</li>
            <li>Toggle device toolbar (Ctrl+Shift+M)</li>
            <li>Select iPhone or Android device</li>
            <li>Check all pages for proper mobile layout</li>
        </ol>
    </div>
</body>
</html>