#!/bin/bash

LOG_DIR="/var/www/api-gateway/storage/logs"
MAX_SIZE=52428800  # 50MB in bytes

for logfile in "$LOG_DIR"/*.log; do
    if [ -f "$logfile" ]; then
        size=$(stat -c%s "$logfile")
        if [ $size -gt $MAX_SIZE ]; then
            echo "WARNUNG: $logfile ist größer als 50MB ($(du -h "$logfile" | cut -f1))"
            # Log automatisch rotieren
            sudo logrotate -f /etc/logrotate.d/askproai
        fi
    fi
done
