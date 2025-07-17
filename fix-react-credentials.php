<?php
// Fix React fetch calls to include credentials

echo "🔧 Fixing React fetch credentials...\n\n";

$files = [
    '/var/www/api-gateway/resources/js/Pages/Portal/Dashboard/Index.jsx',
    '/var/www/api-gateway/resources/js/components/NotificationCenter.jsx',
    '/var/www/api-gateway/resources/js/components/NotificationCenterModern.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Calls/Index.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Appointments/Index.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Billing/Index.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Analytics/Index.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Team/Index.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Settings/Index.jsx',
    '/var/www/api-gateway/resources/js/Pages/Portal/Customers/Index.jsx',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix fetch calls that don't have credentials
    // Pattern 1: fetch with object literal
    $content = preg_replace(
        '/fetch\s*\(\s*([^,]+),\s*\{(?!\s*credentials:)/i',
        'fetch($1, {
            credentials: \'include\',',
        $content
    );
    
    // Pattern 2: fetch with headers only
    $content = preg_replace(
        '/fetch\s*\(\s*([^,]+),\s*\{\s*headers:\s*\{/i',
        'fetch($1, {
            credentials: \'include\',
            headers: {',
        $content
    );
    
    // Check if file was modified
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Fixed: " . basename($file) . "\n";
    } else {
        echo "ℹ️  No changes needed: " . basename($file) . "\n";
    }
}

echo "\n🔍 Also checking for axios usage...\n";

// Check for axios usage
exec("grep -r 'axios' /var/www/api-gateway/resources/js --include='*.js' --include='*.jsx' 2>/dev/null", $axiosFiles);
if (!empty($axiosFiles)) {
    echo "⚠️  Found axios usage in:\n";
    foreach ($axiosFiles as $axiosFile) {
        echo "   - $axiosFile\n";
    }
    echo "\n💡 Add this to axios config:\n";
    echo "   axios.defaults.withCredentials = true;\n";
} else {
    echo "✅ No axios usage found\n";
}

echo "\n✅ Done! Now rebuild with: npm run build\n";