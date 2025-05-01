#!/bin/bash
# Netcup Deployment Script für askproai.de
# Version 1.0 – 2025-04-25

# ❶ Dein Netcup-SSH-User
SSH_USER="hosting215275"
# ❷ IP deines Netcup-Servers
SSH_HOST="152.53.228.178"
# ❸ Remote-Ordner: Produktion
REMOTE_DIR="/var/www/askproai.de"

# Farben
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'

echo -e "${YELLOW}== Deploy AskProAI → Netcup ==${NC}"

# 1) Projekt bauen
echo -e "${GREEN}→ 1) Baue Projekt lokal...${NC}"
npm run build
if [ $? -ne 0 ]; then
  echo -e "${RED}Build fehlgeschlagen. Abbruch.${NC}"
  exit 1
fi

# 2) SSH-Verbindung prüfen
echo -e "${GREEN}→ 2) Teste SSH auf $SSH_HOST...${NC}"
ssh -o BatchMode=yes -o ConnectTimeout=5 "${SSH_USER}@${SSH_HOST}" "echo ok" >/dev/null 2>&1
if [ $? -ne 0 ]; then
  echo -e "${RED}SSH-Verbindung fehlgeschlagen.${NC}"
  exit 1
fi

# 3) Remote-Verzeichnis anlegen
echo -e "${GREEN}→ 3) Erstelle Remote-Ordner $REMOTE_DIR...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" "mkdir -p ${REMOTE_DIR}"

# 4) Dateien übertragen
echo -e "${GREEN}→ 4) Übertrage Dateien per rsync...${NC}"
rsync -avz --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  ./dist/ "${SSH_USER}@${SSH_HOST}:${REMOTE_DIR}"
if [ $? -ne 0 ]; then
  echo -e "${RED}Rsync fehlgeschlagen.${NC}"
  exit 1
fi

# 5) Zugriffsrechte setzen
echo -e "${GREEN}→ 5) Setze Zugriffsrechte...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" "chmod -R 755 ${REMOTE_DIR}"

# 6) Fertig
echo -e "${GREEN}== Deployment erfolgreich! ==${NC}"
echo -e "→ Seite live unter https://${SSH_HOST}"
