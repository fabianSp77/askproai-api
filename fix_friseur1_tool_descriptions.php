<?php

/**
 * Fix Friseur 1 Tool Descriptions
 *
 * Replace generic "z.B. Beratung" with Friseur-specific services
 */

$flowFile = __DIR__ . '/public/friseur1_flow_complete.json';
$flow = json_decode(file_get_contents($flowFile), true);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Fix Friseur 1 Tool Descriptions                         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$fixed = 0;

foreach ($flow['tools'] as &$tool) {
    if (isset($tool['parameters']['properties']['dienstleistung']['description'])) {
        $oldDesc = $tool['parameters']['properties']['dienstleistung']['description'];

        if (strpos($oldDesc, 'Beratung') !== false) {
            // Replace with Friseur-specific examples
            $newDesc = str_replace(
                'z.B. Beratung',
                'z.B. Herrenhaarschnitt, Damenhaarschnitt, Ansatzfärbung',
                $oldDesc
            );

            $tool['parameters']['properties']['dienstleistung']['description'] = $newDesc;

            echo "✅ Fixed tool: {$tool['name']}\n";
            echo "   Old: {$oldDesc}\n";
            echo "   New: {$newDesc}\n";
            echo PHP_EOL;

            $fixed++;
        }
    }
}
unset($tool);

if ($fixed === 0) {
    echo "ℹ️  No fixes needed - all tools already correct!\n";
    exit(0);
}

// Save
$json = json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents($flowFile, $json);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    FIX COMPLETE                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "✅ Fixed {$fixed} tool descriptions\n";
echo "✅ File updated: {$flowFile}\n";
echo PHP_EOL;
