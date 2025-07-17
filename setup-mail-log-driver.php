<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Current mail configuration:\n";
echo "- Driver: " . config('mail.default') . "\n";
echo "- Host: " . config('mail.mailers.smtp.host') . "\n";
echo "- From: " . config('mail.from.address') . "\n\n";

echo "Switching to log driver for testing...\n";

// Update .env file to use log driver
$envPath = base_path('.env');
$envContent = file_get_contents($envPath);

// Backup current .env
file_put_contents($envPath . '.backup_' . date('Y-m-d_H-i-s'), $envContent);

// Replace MAIL_MAILER=smtp with MAIL_MAILER=log
$newEnvContent = preg_replace('/^MAIL_MAILER=.*/m', 'MAIL_MAILER=log', $envContent);

if ($newEnvContent !== $envContent) {
    file_put_contents($envPath, $newEnvContent);
    echo "✅ Updated .env to use log driver\n";
    echo "⚠️  Note: E-Mails werden jetzt in storage/logs/laravel.log gespeichert statt versendet.\n";
    
    // Clear config cache
    \Artisan::call('config:clear');
    echo "✅ Config cache cleared\n";
} else {
    echo "⚠️  Could not update .env file\n";
}

echo "\nTo revert to SMTP, run:\n";
echo "cp " . $envPath . ".backup_" . date('Y-m-d_H-i-s') . " " . $envPath . "\n";
echo "php artisan config:clear\n";