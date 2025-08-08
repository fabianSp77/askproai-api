<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Authenticate
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
auth()->login($user);

echo "<h1>Call Model Relationships Test</h1>";
echo "<pre>";

// Get a call
$call = \App\Models\Call::where('company_id', $user->company_id ?? 1)->first();

if (!$call) {
    die("No calls found");
}

echo "Call ID: " . $call->id . "\n\n";

// Test each attribute directly
echo "Direct Attributes:\n";
echo "=================\n";
$attributes = ['id', 'created_at', 'status', 'duration_sec', 'from_phone', 'to_phone', 'company_id'];
foreach ($attributes as $attr) {
    $value = $call->$attr;
    echo "$attr: " . var_export($value, true) . " (type: " . gettype($value) . ")\n";
}

// Test relationships
echo "\nRelationships:\n";
echo "==============\n";

// Customer relationship
try {
    if (method_exists($call, 'customer')) {
        $customer = $call->customer;
        echo "customer: " . ($customer ? $customer->name : 'NULL') . "\n";
    } else {
        echo "customer: No relationship method\n";
    }
} catch (\Exception $e) {
    echo "customer: Error - " . $e->getMessage() . "\n";
}

// Company relationship
try {
    if (method_exists($call, 'company')) {
        $company = $call->company;
        echo "company: " . ($company ? $company->name : 'NULL') . "\n";
    } else {
        echo "company: No relationship method\n";
    }
} catch (\Exception $e) {
    echo "company: Error - " . $e->getMessage() . "\n";
}

// Check model casts
echo "\nModel Casts:\n";
echo "============\n";
$casts = $call->getCasts();
foreach ($casts as $key => $type) {
    echo "$key => $type\n";
}

// Check hidden attributes
echo "\nHidden Attributes:\n";
echo "==================\n";
$hidden = $call->getHidden();
foreach ($hidden as $attr) {
    echo "- $attr\n";
}

// Check if there are any accessors
echo "\nAccessors/Mutators:\n";
echo "===================\n";
$reflection = new ReflectionClass($call);
$methods = $reflection->getMethods();
foreach ($methods as $method) {
    $name = $method->getName();
    if (str_starts_with($name, 'get') && str_ends_with($name, 'Attribute')) {
        $attr = substr($name, 3, -9);
        echo "Accessor: $attr\n";
    }
}

echo "</pre>";

// Test with raw SQL
echo "<h2>Raw SQL Test:</h2>";
echo "<pre>";
$results = \DB::select("SELECT id, created_at, status, duration_sec, from_phone, to_phone FROM calls WHERE company_id = ? LIMIT 3", [$user->company_id ?? 1]);
foreach ($results as $row) {
    echo "Row: " . json_encode($row) . "\n";
}
echo "</pre>";