<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

$company = Company::find(85);
if ($company) {
    echo "Company: " . $company->name . PHP_EOL;
    echo "Has Cal.com API Key: " . (!empty($company->calcom_api_key) ? "YES" : "NO") . PHP_EOL;
    echo "Cal.com Team Slug: " . ($company->calcom_team_slug ?? "NOT SET") . PHP_EOL;
    
    // Check if we can decrypt the key
    if (!empty($company->calcom_api_key)) {
        try {
            $decrypted = decrypt($company->calcom_api_key);
            echo "API Key starts with: " . substr($decrypted, 0, 10) . "..." . PHP_EOL;
        } catch (Exception $e) {
            echo "Error decrypting API key: " . $e->getMessage() . PHP_EOL;
        }
    }
} else {
    echo "Company with ID 85 not found!" . PHP_EOL;
}