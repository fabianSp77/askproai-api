<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Authenticate
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
auth()->login($user);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Standalone Table Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css">
</head>
<body class="p-8">
    <h1 class="text-2xl font-bold mb-4">Standalone Table Test</h1>
    
    <?php
    // Get calls
    $calls = \App\Models\Call::where('company_id', $user->company_id ?? 1)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    ?>
    
    <div class="mb-4">
        <strong>Found <?= count($calls) ?> calls</strong>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($calls as $call): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $call->id ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $call->created_at->format('Y-m-d H:i') ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        <?= $call->status ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= gmdate('i:s', $call->duration_sec) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $call->from_phone ?: '—' ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $call->to_phone ?: '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="mt-8">
        <h2 class="text-xl font-bold mb-2">Debug Info</h2>
        <pre class="bg-gray-100 p-4 rounded">
<?php
// Show what Filament sees
use App\Filament\Admin\Resources\CallResource;

$query = CallResource::getEloquentQuery();
echo "Filament Query SQL: " . $query->toSql() . "\n";
echo "Filament Query Count: " . $query->count() . "\n";

// Get one record and show all attributes
$sampleCall = $query->first();
if ($sampleCall) {
    echo "\nSample Call Attributes:\n";
    foreach ($sampleCall->getAttributes() as $key => $value) {
        echo "  $key: " . var_export($value, true) . "\n";
    }
}
?>
        </pre>
    </div>
    
    <div class="mt-4">
        <a href="/admin/calls" class="text-blue-600 hover:text-blue-800">Go to Filament Calls Page</a>
    </div>
</body>
</html>