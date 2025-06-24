<?php
$content = file_get_contents('app/Filament/Admin/Resources/UltimateCustomerResource.php');
$lines = explode(PHP_EOL, $content);
$braces = 0;
$parens = 0;
$brackets = 0;
$inMethod = false;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    if (strpos($line, 'public static function table') !== false) {
        $inMethod = true;
        echo "\n=== Starting table() method at line " . ($i + 1) . " ===\n\n";
    }
    
    if ($inMethod) {
        $prevBraces = $braces;
        $prevParens = $parens;
        $prevBrackets = $brackets;
        
        $braces += substr_count($line, '{') - substr_count($line, '}');
        $parens += substr_count($line, '(') - substr_count($line, ')');
        $brackets += substr_count($line, '[') - substr_count($line, ']');
        
        if ($prevBraces != $braces || $prevParens != $parens || $prevBrackets != $brackets) {
            printf("Line %3d: {:%2d (%+d), (:%2d (%+d), [:%2d (%+d) | %s\n", 
                $i + 1, 
                $braces, $braces - $prevBraces,
                $parens, $parens - $prevParens,
                $brackets, $brackets - $prevBrackets,
                trim($line)
            );
        }
        
        if ($braces == 0 && strpos($line, '}') !== false && $inMethod) {
            echo "\n=== End of table() method at line " . ($i + 1) . " ===\n";
            echo "Final counts - Braces: $braces, Parens: $parens, Brackets: $brackets\n";
            break;
        }
    }
}

if ($parens != 0) {
    echo "\n⚠️ PARENTHESIS MISMATCH! Count: $parens\n";
}
if ($brackets != 0) {
    echo "\n⚠️ BRACKETS MISMATCH! Count: $brackets\n";
}