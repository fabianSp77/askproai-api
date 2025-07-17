<?php
// Update React components to use optional auth endpoints temporarily

echo "🔄 Updating React API endpoints to use optional auth...\n";

// Dashboard component
$dashboardFile = '/var/www/api-gateway/resources/js/Pages/Portal/Dashboard/Index.jsx';
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    
    // Update dashboard API endpoint
    $content = str_replace(
        '/business/api/dashboard',
        '/business/api-optional/dashboard',
        $content
    );
    
    file_put_contents($dashboardFile, $content);
    echo "✅ Updated Dashboard component\n";
}

// NotificationCenter component
$notificationFile = '/var/www/api-gateway/resources/js/components/NotificationCenter.jsx';
if (file_exists($notificationFile)) {
    $content = file_get_contents($notificationFile);
    
    // Update notifications API endpoint
    $content = str_replace(
        '/business/api/notifications',
        '/business/api-optional/notifications',
        $content
    );
    
    file_put_contents($notificationFile, $content);
    echo "✅ Updated NotificationCenter component\n";
}

// NotificationCenterModern component
$notificationModernFile = '/var/www/api-gateway/resources/js/components/NotificationCenterModern.jsx';
if (file_exists($notificationModernFile)) {
    $content = file_get_contents($notificationModernFile);
    
    // Update notifications API endpoint
    $content = str_replace(
        '/business/api/notifications',
        '/business/api-optional/notifications',
        $content
    );
    
    file_put_contents($notificationModernFile, $content);
    echo "✅ Updated NotificationCenterModern component\n";
}

echo "\n🎯 Done! Now rebuild the React app:\n";
echo "   npm run build\n";
echo "\nOr for development:\n";
echo "   npm run dev\n";