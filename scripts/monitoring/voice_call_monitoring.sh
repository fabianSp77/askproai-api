#!/bin/bash
# Live monitoring script for first test call
# Run this in a separate terminal while making the test call

echo "=== LIVE TEST CALL MONITORING ==="
echo ""
echo "Start monitoring at: $(date)"
echo "Press Ctrl+C to stop"
echo ""
echo "--------------------------------------"
echo ""

tail -f /var/www/api-gateway/storage/logs/laravel.log | grep --line-buffered -E \
  "Service extraction|Service extraction complete|Distributed lock|SAGA Compensation|ORPHANED BOOKING|Cal.com booking successful|Local appointment record created"
