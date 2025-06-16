<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Teste Mail direkt
    $to = 'fabianspitzer@icloud.com';
    $subject = 'Test-E-Mail von AskProAI';
    $message = 'Dies ist eine Test-E-Mail. Wenn Sie diese erhalten, funktioniert das Mail-System.';
    
    // Nutze Laravel Mail Facade korrekt
    \Illuminate\Support\Facades\Mail::raw($message, function($mail) use ($to, $subject) {
        $mail->to($to)
             ->subject($subject);
    });
    
    echo "E-Mail wurde gesendet!\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
