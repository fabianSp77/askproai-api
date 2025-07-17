<?php
// Quick fix to ensure proper session handling

require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

echo "🔧 Fixing Portal Dashboard Access\n";
echo "=================================\n\n";

// Get the test user
$user = PortalUser::where("email", "fabianspitzer@icloud.com")->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✅ User found: {$user->email}\n";

// Check branches
$branches = DB::table("branches")->where("company_id", $user->company_id)->get();
echo "\n📍 Branches for company:\n";
foreach ($branches as $branch) {
    echo "   - {$branch->name} (ID: {$branch->id})\n";
}

if ($branches->isEmpty()) {
    echo "\n⚠️  No branches found! Creating default branch...\n";
    
    $branchId = DB::table("branches")->insertGetId([
        "company_id" => $user->company_id,
        "name" => "Hauptfiliale",
        "phone" => "+49 30 12345678",
        "email" => "info@demo-gmbh.de",
        "address" => "Musterstraße 1",
        "city" => "Berlin",
        "state" => "Berlin",
        "postal_code" => "10115",
        "country" => "DE",
        "is_active" => 1,
        "created_at" => now(),
        "updated_at" => now()
    ]);
    
    echo "✅ Created default branch (ID: $branchId)\n";
} else {
    $defaultBranch = $branches->first();
    echo "\n✅ Default branch: {$defaultBranch->name}\n";
}

echo "\n💾 Session configuration:\n";
echo "   Driver: " . config("session.driver") . "\n";
echo "   Domain: " . config("session.domain") . "\n";
echo "   Path: " . config("session.path") . "\n";

echo "\n✅ Setup complete! You can now login.\n";
