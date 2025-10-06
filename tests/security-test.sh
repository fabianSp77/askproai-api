#!/bin/bash

echo "=== Security Testing ==="
echo ""
echo "1. SQL Injection Test (safe query):"
curl -s -o /dev/null -w "Response: %{http_code}\n" -k "https://api.askproai.de/api/health?test=1"

echo ""
echo "2. XSS Prevention Test:"
curl -s -o /dev/null -w "Response: %{http_code}\n" -k "https://api.askproai.de/api/health?test=alert"

echo ""
echo "3. Directory Traversal Test:"
curl -s -o /dev/null -w "Response: %{http_code}\n" -k "https://api.askproai.de/../../../etc/passwd"

echo ""
echo "4. Sensitive File Access Tests:"
echo -n "  .env file: "
curl -s -o /dev/null -w "%{http_code}\n" -k "https://api.askproai.de/.env"
echo -n "  .git directory: "
curl -s -o /dev/null -w "%{http_code}\n" -k "https://api.askproai.de/.git/config"
echo -n "  composer.json: "
curl -s -o /dev/null -w "%{http_code}\n" -k "https://api.askproai.de/composer.json"

echo ""
echo "5. Rate Limiting Test (10 rapid requests):"
for i in {1..10}; do
    code=$(curl -s -o /dev/null -w "%{http_code}" -k https://api.askproai.de/api/health)
    echo -n "$code "
done
echo ""

echo ""
echo "6. HTTPS/SSL Check:"
curl -I -s https://api.askproai.de/admin/login -k 2>&1 | grep -E "^HTTP|Strict-Transport-Security" | head -2

echo ""
echo "7. CORS Headers Check:"
curl -I -s https://api.askproai.de/api/health -k 2>&1 | grep -i "access-control"