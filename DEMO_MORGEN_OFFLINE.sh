#!/bin/bash
# 🚀 DEMO-SETUP FÜR MORGEN (OFFLINE)

echo "================================================"
echo "🚀 DEMO-VORBEREITUNG FÜR 17.07.2025"
echo "================================================"
echo ""
echo "1️⃣  PRÄSENTATION ÖFFNEN:"
echo "   firefox file:///var/www/api-gateway/demo-praesentation-2025-07-17.html"
echo "   ODER"
echo "   google-chrome file:///var/www/api-gateway/demo-praesentation-2025-07-17.html"
echo ""
echo "2️⃣  ROI-KALKULATION ÖFFNEN:"
echo "   firefox file:///var/www/api-gateway/roi-kalkulation.html"
echo ""
echo "3️⃣  NAVIGATION IN DER PRÄSENTATION:"
echo "   → Pfeiltaste rechts = Nächste Slide"
echo "   ← Pfeiltaste links = Vorherige Slide" 
echo "   F = Fullscreen"
echo "   ESC = Fullscreen verlassen"
echo ""
echo "4️⃣  BACKUP AUF USB-STICK:"
echo "   cp demo-praesentation-2025-07-17.html /media/usb/"
echo "   cp roi-kalkulation.html /media/usb/"
echo "   cp DEMO_SPICKZETTEL_2025-07-17.md /media/usb/"
echo ""
echo "================================================"
echo "💡 WICHTIGE ZAHLEN FÜR DIE DEMO:"
echo "================================================"
echo "   • 270 Anrufe verarbeitet"
echo "   • 68.9% After-Hours (186 Anrufe!)"
echo "   • 5.911€ verwaltetes Guthaben"
echo "   • 20% Provision = 1.182€"
echo "   • 400€/Monat bei 20 Kunden"
echo "   • 4.800€/Jahr bei 20 Kunden"
echo ""
echo "================================================"
echo "🎯 REMEMBER:"
echo "================================================"
echo "Du verkaufst ein GESCHÄFTSMODELL, keine Software!"
echo "Die Story ist wichtiger als perfekte Buttons!"
echo ""
echo "VIEL ERFOLG! DU ROCKST DAS! 🚀"
echo "================================================"

# Optional: Dateien direkt öffnen
read -p "Soll ich die Präsentation jetzt öffnen? (j/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Jj]$ ]]
then
    firefox file:///var/www/api-gateway/demo-praesentation-2025-07-17.html &
    echo "✅ Präsentation geöffnet!"
fi