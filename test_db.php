<?php

try {
    $dsn = 'mysql:host=127.0.0.1;dbname=askproai_db';
    $username = 'askproai_user';
    $password = 'Vb39!pLc#7Lqwp$X';
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ PDO-Verbindung erfolgreich!\n";

    $stmt = $pdo->query('SHOW TABLES');
    echo "Tabellen in askproai_db:\n";
    while ($row = $stmt->fetch()) {
        echo '- '.$row[array_key_first($row)]."\n";
    }
} catch (PDOException $e) {
    echo '❌ Verbindungsfehler: '.$e->getMessage()."\n";
}
