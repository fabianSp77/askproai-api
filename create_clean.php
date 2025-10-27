<?php

$json = json_decode(file_get_contents('public/retell_import_fixed.json'), true);

// Remove potentially problematic fields
unset($json['tools']);
unset($json['begin_after_user_silence_ms']);

// Ensure we have start_node_id
$json['start_node_id'] = 'node_01_initialization';

// Save clean version
file_put_contents(
    'public/retell_clean.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✅ Created clean version" . PHP_EOL;
echo "File: public/retell_clean.json" . PHP_EOL;
echo "Size: " . filesize('public/retell_clean.json') . " bytes" . PHP_EOL;
