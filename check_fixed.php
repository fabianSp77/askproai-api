<?php

$json = json_decode(file_get_contents('public/retell_import_fixed.json'), true);
echo "Total nodes: " . count($json['nodes']) . PHP_EOL;
echo "Checking each node..." . PHP_EOL . PHP_EOL;

foreach ($json['nodes'] as $i => $node) {
    $issues = [];

    if (!isset($node['instruction']) || !isset($node['instruction']['type'])) {
        $issues[] = 'MISSING instruction.type';
    }

    if (!isset($node['edges'])) {
        $issues[] = 'MISSING edges';
    }

    if ($node['type'] === 'end' && isset($node['instruction'])) {
        $issues[] = 'END node should NOT have instruction';
    }

    if (!empty($issues)) {
        echo "Node: " . $node['id'] . " - " . implode(', ', $issues) . PHP_EOL;
    }
}

echo PHP_EOL . "Check complete" . PHP_EOL;
