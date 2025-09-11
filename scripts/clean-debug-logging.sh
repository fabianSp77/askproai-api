#!/bin/bash

# Script to clean up debug logging in production code
# Wraps debug statements in config('app.debug') checks

echo "🧹 Cleaning up debug logging in production code..."

# Convert CalcomService debug statements to conditional
echo "Fixing CalcomService debug logging..."
sed -i "s/Log::debug(\[/if (config('app.debug')) { Log::debug([/g" /var/www/api-gateway/app/Services/CalcomService.php
sed -i "/if (config('app.debug')) { Log::debug/a\        }" /var/www/api-gateway/app/Services/CalcomService.php

# Convert CalcomV2Service debug statements to conditional  
echo "Fixing CalcomV2Service debug logging..."
sed -i "s/Log::debug(\[/if (config('app.debug')) { Log::debug([/g" /var/www/api-gateway/app/Services/CalcomV2Service.php
sed -i "/if (config('app.debug')) { Log::debug/a\        }" /var/www/api-gateway/app/Services/CalcomV2Service.php

# Convert CalcomHybridService debug statements to conditional
echo "Fixing CalcomHybridService debug logging..."
sed -i "s/Log::debug(\[/if (config('app.debug')) { Log::debug([/g" /var/www/api-gateway/app/Services/CalcomHybridService.php
sed -i "/if (config('app.debug')) { Log::debug/a\        }" /var/www/api-gateway/app/Services/CalcomHybridService.php

# Remove or comment out CsrfDebugMiddleware debug logging
echo "Disabling CsrfDebugMiddleware debug logging..."
sed -i "s/Log::channel('debug_csrf')->debug/\/\/ Log::channel('debug_csrf')->debug/g" /var/www/api-gateway/app/Http/Middleware/CsrfDebugMiddleware.php

# Wrap ViewCacheService debug in config check
echo "Fixing ViewCacheService debug logging..."
sed -i "s/Log::debug(\"Warmed/if (config('app.debug')) { Log::debug(\"Warmed/g" /var/www/api-gateway/app/Services/ViewCacheService.php
sed -i "/if (config('app.debug')) { Log::debug(\"Warmed/a\                    }" /var/www/api-gateway/app/Services/ViewCacheService.php

# Wrap MLServiceClient debug in config check
echo "Fixing MLServiceClient debug logging..."
sed -i "s/Log::debug(\"Returning/if (config('app.debug')) { Log::debug(\"Returning/g" /var/www/api-gateway/app/Services/MLServiceClient.php
sed -i "/if (config('app.debug')) { Log::debug(\"Returning/a\            }" /var/www/api-gateway/app/Services/MLServiceClient.php

echo "✅ Debug logging cleanup complete!"
echo ""
echo "Summary of changes:"
echo "- Wrapped debug statements in config('app.debug') checks"
echo "- Disabled CsrfDebugMiddleware logging"
echo "- All debug logging now only runs when APP_DEBUG=true"
echo ""
echo "To verify, run: grep -r 'Log::debug' app/ --include='*.php' | grep -v 'config.*app.debug'"