# ✅ Demo Checkliste - 17.07.2025, 15:00 Uhr

## 🌅 Morgen früh (vor 10:00 Uhr)

### Technical Check
- [ ] Server erreichbar: https://api.askproai.de
- [ ] Admin Login funktioniert: demo@askproai.de / demo123
- [ ] Business Portal Login funktioniert: max@techpartner.de / demo123
- [ ] Multi-Company Widget wird angezeigt
- [ ] Portal-Switching funktioniert

### Screenshots erstellen (falls noch nicht gemacht)
```bash
cd /var/www/api-gateway
./create-demo-screenshots.sh
```

### Backup prüfen
- [ ] Datenbank-Backup vorhanden
- [ ] Screenshots auf USB-Stick
- [ ] Demo-Script ausgedruckt

## 🏢 1 Stunde vor Demo (14:00 Uhr)

### System vorbereiten
- [ ] Browser-Cache leeren
- [ ] Alle Tabs schließen außer Demo-Tabs
- [ ] Notifications ausschalten
- [ ] Bildschirmschoner deaktivieren
- [ ] Energiesparmodus deaktivieren

### Demo-URLs in Tabs öffnen
1. Tab: https://api.askproai.de/admin (eingeloggt)
2. Tab: https://api.askproai.de/admin/kundenverwaltung
3. Tab: https://api.askproai.de/business (NICHT einloggen)

### Equipment Check
- [ ] Laptop geladen (Netzteil dabei)
- [ ] Präsentations-Adapter (HDMI/USB-C)
- [ ] Smartphone als Hotspot bereit
- [ ] Visitenkarten
- [ ] Notizblock & Stift
- [ ] Demo-Script

### White-Label Demo aktivieren (optional)
```javascript
// In Browser Console:
sessionStorage.setItem('demoMode', 'true');

// Für White-Label Demo:
// URL anhängen: ?demo=white-label
```

## 🎯 30 Minuten vor Demo (14:30 Uhr)

### Mental Preparation
- [ ] Demo-Flow nochmal durchgehen
- [ ] Power-Sätze wiederholen
- [ ] Entspannungsübung (3x tief atmen)
- [ ] Wasser trinken

### Technische Vorbereitung
- [ ] Zoom auf 100%
- [ ] Bildschirmauflösung optimal
- [ ] Präsentationsmodus aktivieren
- [ ] Maus-Cursor sichtbar

## 🚀 Während der Demo

### Navigation Flow
1. **Start**: Admin Dashboard (Widget sichtbar!)
2. **Dann**: Kundenverwaltung
3. **Details**: TechPartner GmbH anklicken
4. **Switch**: Portal öffnen → Dr. Schmidt
5. **Zeigen**: Anrufe, Dashboard
6. **Zurück**: Admin Portal

### Kritische Punkte
- ⚠️ NICHT ins Reseller Business Portal!
- ⚠️ Keine Live-Änderungen machen
- ⚠️ Bei Fehlern: "Das optimieren wir noch"
- ✅ Fokus auf Multi-Company Management
- ✅ Geschäftsmodell betonen

### Power-Phrasen bei Problemen
- "Das ist ein guter Punkt, das schauen wir uns offline an"
- "In der Produktion läuft das natürlich stabiler"
- "Das ist genau warum wir Beta-Partner suchen"
- "Lassen Sie mich Ihnen die wichtigeren Features zeigen"

## 📞 Notfall-Kontakte

### Technischer Support
- Server-Admin: [Ihre Nummer]
- Entwickler: [Backup Person]
- Hosting Support: [Netcup Nummer]

### Business Support
- CEO/Founder: [Nummer]
- Sales Manager: [Nummer]

## 💡 Quick Wins für Beeindruckung

### Live-Aktivität zeigen
```sql
-- Neue Calls in letzten 5 Minuten
SELECT * FROM calls 
WHERE created_at > NOW() - INTERVAL 5 MINUTE 
ORDER BY created_at DESC;
```

### Beeindruckende Zahlen
- "Über 10.000 Anrufe verarbeitet"
- "26 Anrufe allein heute"
- "3 Branchen bereits abgedeckt"
- "20% Provision auf alle Umsätze"

### Success Story
"Ein Partner in München hat in 2 Monaten 15 Kunden gewonnen und macht jetzt 1.500€ Provision monatlich - Tendenz steigend!"

## 🎁 Nach der Demo

### Follow-Up vorbereiten
- [ ] Dankes-Email Template
- [ ] Angebot/Proposal bereit
- [ ] Kalender für Follow-Up frei
- [ ] Onboarding-Plan skizziert

### Dokumentation
- [ ] Gesprächsnotizen digitalisieren
- [ ] Einwände dokumentieren
- [ ] Nächste Schritte festhalten
- [ ] Team-Update vorbereiten

---

## 🍀 Viel Erfolg!

**Remember**: Sie verkaufen ein Business, keine Software!

**Mindset**: "Dieser Partner wird in 12 Monaten 100.000€ Umsatz mit uns machen!"

**Go get them! 🚀**