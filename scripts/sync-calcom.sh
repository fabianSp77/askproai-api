#!/bin/bash
# Cal.com Sync Script

cd /var/www/api-gateway
php artisan calcom:import-directly --days=7 --future=30 >> storage/logs/calcom-sync.log 2>&1