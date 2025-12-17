<?php
// Force browser to never cache this file
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Clear PHP's own file cache
clearstatcache(true);

// Read and serve the HTML file (always fresh from disk)
$filepath = '/var/www/api-gateway/public/backend-api-tester-v4-multi-tenant.html';
$content = file_get_contents($filepath);

header("Content-Type: text/html; charset=UTF-8");
echo $content;
