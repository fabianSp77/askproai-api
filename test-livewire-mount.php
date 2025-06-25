<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Filament\Admin\Pages\RetellUltimateControlCenter;
use App\Models\User;

// Set up auth
$user = User::where('email', 'fabian@askproai.de')->first();
auth()->login($user);

echo "=== TESTING CONTROL CENTER MOUNT ===\n\n";

// Create instance
$controlCenter = new RetellUltimateControlCenter();

echo "1. Before mount():\n";
echo "   Agents count: " . count($controlCenter->agents) . "\n";
echo "   Error: " . ($controlCenter->error ?? 'None') . "\n\n";

echo "2. Calling mount()...\n";
$controlCenter->mount();

echo "\n3. After mount():\n";
echo "   Agents count: " . count($controlCenter->agents) . "\n";
echo "   Phone numbers count: " . count($controlCenter->phoneNumbers) . "\n";
echo "   Error: " . ($controlCenter->error ?? 'None') . "\n";
echo "   Has retell service: " . ($controlCenter->retellService ? 'YES' : 'NO') . "\n\n";

echo "4. Agent names:\n";
foreach (array_slice($controlCenter->agents, 0, 5) as $agent) {
    echo "   - " . ($agent['display_name'] ?? $agent['agent_name'] ?? 'Unknown') . "\n";
}

echo "\n5. Public properties check:\n";
$reflection = new ReflectionClass($controlCenter);
$properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
foreach ($properties as $prop) {
    $name = $prop->getName();
    $value = $controlCenter->$name;
    if (is_array($value)) {
        echo "   $name: array(" . count($value) . ")\n";
    } elseif (is_string($value)) {
        echo "   $name: '$value'\n";
    } elseif (is_bool($value)) {
        echo "   $name: " . ($value ? 'true' : 'false') . "\n";
    } elseif (is_null($value)) {
        echo "   $name: null\n";
    }
}