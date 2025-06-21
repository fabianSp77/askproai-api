<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the knowledge service
$knowledgeService = app(App\Services\KnowledgeBaseService::class);

// Test search
$query = $argv[1] ?? 'webhook';
echo "Searching for: {$query}\n";
echo str_repeat('-', 50) . "\n";

$results = $knowledgeService->search($query);

if ($results->isEmpty()) {
    echo "No results found.\n";
} else {
    echo "Found {$results->count()} results:\n\n";
    
    foreach ($results as $index => $document) {
        echo ($index + 1) . ". {$document->title}\n";
        echo "   Category: " . ($document->category->name ?? 'Uncategorized') . "\n";
        echo "   Excerpt: " . Str::limit($document->excerpt, 100) . "\n";
        echo "   Views: {$document->view_count} | Helpful: {$document->helpful_count}\n";
        echo "   URL: /knowledge/{$document->slug}\n";
        echo "\n";
    }
}