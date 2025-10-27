<?php

$json = json_decode(file_get_contents('public/retell_import.json'), true);

// FIX: Ensure ALL nodes have edges property
foreach ($json['nodes'] as $key => $node) {
    if (!isset($node['edges'])) {
        $json['nodes'][$key]['edges'] = [];
        echo "Added empty edges to: " . $node['id'] . PHP_EOL;
    }
}

// Save corrected version
file_put_contents(
    'public/retell_import_fixed.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo PHP_EOL . "✅ Fixed file created: public/retell_import_fixed.json" . PHP_EOL;
echo "File size: " . filesize('public/retell_import_fixed.json') . " bytes" . PHP_EOL;
