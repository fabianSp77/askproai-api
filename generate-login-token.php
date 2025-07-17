<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Cache;

// Find a working user
$user = PortalUser::where('email', 'admin+1@askproai.de')
    ->where('is_active', true)
    ->first();

if (!$user) {
    die("No active user found!\n");
}

// Generate multiple tokens with longer validity
$tokens = [];
for ($i = 1; $i <= 3; $i++) {
    $token = bin2hex(random_bytes(32));
    Cache::put('portal_login_token_' . $token, $user->id, now()->addHours(24)); // 24 hours validity
    $tokens[] = $token;
}

echo "=== LOGIN TOKENS GENERATED ===\n\n";
echo "User: {$user->email}\n";
echo "Company: {$user->company->name}\n";
echo "Valid for: 24 hours\n\n";

echo "🔗 Direct Login URLs:\n\n";

foreach ($tokens as $index => $token) {
    echo "Option " . ($index + 1) . ":\n";
    echo "https://api.askproai.de/business/login-with-token?token={$token}\n\n";
}

echo "📋 After login, navigate to:\n";
echo "- /business/calls → Test new features\n";
echo "- /business/billing → Test Stripe\n\n";

echo "✅ Click any link above to login immediately!\n";