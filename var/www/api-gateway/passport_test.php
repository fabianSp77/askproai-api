<?php
$privateKeyPath = '/var/www/api-gateway/storage/oauth-private.key';
$publicKeyPath = '/var/www/api-gateway/storage/oauth-public.key';

echo "Teste OAuth-Schlüssel\n";

if (!file_exists($privateKeyPath)) {
    echo "FEHLER: Private Key existiert nicht: $privateKeyPath\n";
} else {
    echo "Private Key existiert: OK\n";
}

if (!is_readable($privateKeyPath)) {
    echo "Private Key NICHT lesbar!\n";
} else {
    echo "Private Key lesbar: OK\n";
}

$keyContent = file_get_contents($privateKeyPath);
if (!$keyContent) {
    echo "FEHLER: Private Key konnte nicht gelesen werden!\n";
} else {
    echo "Private Key konnte erfolgreich gelesen werden.\n";
}
