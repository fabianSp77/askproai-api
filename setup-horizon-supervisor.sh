#!/bin/bash

echo "=== Horizon Supervisor Setup ==="
echo "Datum: $(date)"
echo

# Check if supervisor is installed
if ! command -v supervisord &> /dev/null; then
    echo "❌ Supervisor ist nicht installiert!"
    echo "Installiere mit: apt-get install supervisor"
    exit 1
fi

# Create supervisor config for Horizon
cat > /etc/supervisor/conf.d/horizon.conf << 'EOF'
[program:horizon]
process_name=%(program_name)s
command=php /var/www/api-gateway/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/horizon.log
stopwaitsecs=3600
EOF

echo "✅ Supervisor Konfiguration erstellt"

# Reload supervisor
supervisorctl reread
supervisorctl update

# Start Horizon
supervisorctl start horizon

echo
echo "✅ Horizon wird jetzt von Supervisor verwaltet"
echo
echo "Nützliche Befehle:"
echo "- supervisorctl status          # Status anzeigen"
echo "- supervisorctl restart horizon # Horizon neustarten"
echo "- supervisorctl stop horizon    # Horizon stoppen"
echo "- supervisorctl start horizon   # Horizon starten"
echo
echo "Logs: /var/www/api-gateway/storage/logs/horizon.log"