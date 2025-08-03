<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

echo "=== Debugging Session Key Mismatch ===\n\n";

// Check the actual class name
$className = \App\Models\PortalUser::class;
echo "1. PortalUser class name: $className\n";
echo "   SHA1 hash: " . sha1($className) . "\n";

// Check if there's a different class being used
echo "\n2. Checking auth configuration:\n";
$guard = auth()->guard('portal');
$provider = $guard->getProvider();
$model = $provider->createModel();

echo "   Provider class: " . get_class($provider) . "\n";
echo "   Model class: " . get_class($model) . "\n";
echo "   Model class constant: " . $model::class . "\n";
echo "   SHA1 of model class: " . sha1($model::class) . "\n";

// Check what the guard getName() returns
echo "\n3. Guard session key generation:\n";
if (method_exists($guard, 'getName')) {
    echo "   Guard name: " . $guard->getName() . "\n";
}

// Check actual session key used
echo "\n4. Checking actual session keys after login:\n";
auth()->guard('portal')->logout();
session()->flush();

// Login
$user = \App\Models\PortalUser::find(41);
auth()->guard('portal')->login($user);

echo "   Session keys after login: " . implode(', ', array_keys(session()->all())) . "\n";

// Look for any key containing 'login'
foreach (session()->all() as $key => $value) {
    if (strpos($key, 'login') !== false) {
        echo "   Found login key: $key = $value\n";
    }
}