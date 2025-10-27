<?php
// DIREKTER Test OHNE Laravel - beweist dass nginx funktioniert
file_put_contents('/tmp/direct_hit.log', date('Y-m-d H:i:s') . " - Direct PHP hit!\n", FILE_APPEND);
echo json_encode(['status' => 'direct_php_works', 'time' => date('Y-m-d H:i:s')]);
