#!/usr/bin/env bash
set -euo pipefail
MAIL_USER='askproai-de-0001'
MAIL_PASS='Qwe421as1!1'

echo -e "\n🔎 1) Port-Reachability"
for p in 465 587; do
    (echo > /dev/tcp/smtps.udag.de/$p) >/dev/null 2>&1 \
        && echo "   ✅ Port $p erreichbar" \
        || echo "   ❌ Port $p BLOCKIERT"
done

echo -e "\n🔎 2) TLS-Handshake (openssl s_client –brief)"
for p in 465 587; do
  echo -e "\n--- Port $p ---"
  if [ "$p" = "465" ]; then
     openssl s_client -connect smtps.udag.de:$p -brief -servername smtps.udag.de </dev/null | head -n5
  else
     openssl s_client -starttls smtp -connect smtps.udag.de:$p -brief -servername smtps.udag.de </dev/null | head -n5
  fi
done

echo -e "\n🔎 3) SMTP-Login-Test (EXPECT 235 Authentication successful)"
python3 - <<PY
import smtplib, ssl, base64, sys
user, pwd = "$MAIL_USER", "$MAIL_PASS"
ctx = ssl.create_default_context()
for port, starttls in ((465, False), (587, True)):
    try:
        if starttls:
            s = smtplib.SMTP("smtps.udag.de", port, timeout=10)
            s.starttls(context=ctx)
        else:
            s = smtplib.SMTP_SSL("smtps.udag.de", port, context=ctx, timeout=10)
        code, _ = s.login(user, pwd)
        print(f"   ✅ Port {port}: Login ok (code {code})")
        s.quit()
    except Exception as e:
        print(f"   ❌ Port {port}: {e}")
PY
