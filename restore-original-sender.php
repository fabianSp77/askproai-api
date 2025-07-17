<?php

echo "=== Restore Original Sender ===\n\n";

echo "Sobald die Domain in Resend verifiziert ist, führen Sie dieses Skript aus:\n\n";

echo "1. Ändern Sie in .env:\n";
echo "   MAIL_FROM_ADDRESS=\"info@askproai.de\"\n\n";

echo "2. Führen Sie aus:\n";
echo "   php artisan config:cache\n\n";

echo "3. Testen Sie eine E-Mail:\n";
echo "   php test-business-portal-email-with-verified-sender.php\n\n";

echo "Die E-Mails sollten dann wieder von info@askproai.de gesendet werden.\n";