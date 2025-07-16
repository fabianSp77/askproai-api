<?php
require_once __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\PortalUser;

$company = Company::first();
if ($company) {
    echo "Company: " . $company->name . " (ID: " . $company->id . ")\n";
    
    $portalUsers = PortalUser::where("company_id", $company->id)
        ->where("is_active", true)
        ->get();
        
    echo "Active Portal Users: " . $portalUsers->count() . "\n";
    foreach ($portalUsers as $user) {
        echo "  - " . $user->name . " (" . $user->email . ")\n";
    }
}

