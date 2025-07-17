<?php
// Test specific language translations
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test languages
$languages = ['de', 'en', 'es', 'fr', 'it', 'tr', 'nl', 'pl', 'pt', 'ru', 'ja', 'zh'];

$results = [];

foreach ($languages as $lang) {
    $request = Illuminate\Http\Request::create(
        "/admin-api/translations/{$lang}",
        'GET',
        [],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer 45|WYhFfzNjL2H6T0dTaZVaOJoCPQUAr2ktsH02QqPu34d2e786'
        ]
    );

    $response = $kernel->handle($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    $results[$lang] = [
        'status' => $response->getStatusCode(),
        'locale' => $data['locale'] ?? 'error',
        'translation_count' => isset($data['translations']) ? count($data['translations']) : 0,
        'sample_translations' => isset($data['translations']) ? array_slice($data['translations'], 0, 3, true) : []
    ];
    
    $kernel->terminate($request, $response);
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);