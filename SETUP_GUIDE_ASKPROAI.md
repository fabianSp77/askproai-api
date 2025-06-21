# 🚀 AskProAI Setup Guide

## Willkommen im AskProAI Admin Panel!

Das System ist jetzt vollständig installiert und läuft. Hier ist deine Schritt-für-Schritt Anleitung:

## 📋 Setup Checkliste

### 1. **Firma anlegen** (5 Minuten)
- Klicke auf "🚀 Neue Firma anlegen" im Menü
- Fülle die Firmendaten aus:
  - Firmenname
  - Kontaktdaten
  - Branche
  - Zeitzone (wichtig für Terminbuchungen!)

### 2. **Filiale erstellen** (5 Minuten)
Nach dem Anlegen der Firma:
- Gehe zu "Filialen" 
- Klicke auf "Neue Filiale"
- **WICHTIG**: Trage die Telefonnummer ein, über die Kunden anrufen
- Diese Telefonnummer wird für die Zuordnung der Anrufe verwendet!

### 3. **Mitarbeiter anlegen** (10 Minuten)
- Gehe zu "Mitarbeiter"
- Erstelle für jeden Mitarbeiter einen Eintrag
- Weise sie der entsprechenden Filiale zu
- Definiere Arbeitszeiten

### 4. **Services/Dienstleistungen** (10 Minuten)
- Gehe zu "Services"
- Lege alle angebotenen Dienstleistungen an
- Definiere Dauer und Preis
- Weise Services den Mitarbeitern zu

### 5. **Cal.com Integration** (20 Minuten)
- Gehe zu "Cal.com Sync Status"
- Trage deinen Cal.com API Key ein
- Importiere Event Types über "Event-Type Import"
- Verknüpfe Event Types mit deinen Services

### 6. **Retell.ai Integration** (30 Minuten)
**In Retell.ai Dashboard:**
1. Erstelle einen neuen Agent
2. Konfiguriere den Webhook:
   - URL: `https://api.askproai.de/api/retell/webhook`
   - Events: `call_started`, `call_ended`, `call_analyzed`
3. Kopiere die Agent ID

**In AskProAI:**
- Trage die Retell Agent ID in der Filiale ein
- Speichere den Webhook Secret in den Einstellungen

### 7. **Telefonnummer verknüpfen**
- Leite deine Geschäftstelefonnummer zu Retell.ai weiter
- Oder nutze eine Retell.ai Test-Nummer
- Stelle sicher, dass die Nummer mit der Filiale verknüpft ist

## 🧪 Test des Systems

### Testanruf durchführen:
1. Rufe die konfigurierte Nummer an
2. Der AI-Agent sollte sich melden
3. Vereinbare einen Testtermin
4. Prüfe im Dashboard:
   - "Anrufe" - sollte den Anruf zeigen
   - "Termine" - sollte die Buchung zeigen
   - "Webhook Monitor" - sollte die Events zeigen

## 📊 Monitoring

### Dashboards verfügbar:
- **Grafana**: http://localhost:3000 (admin/admin)
  - System Metrics
  - Application Performance
  - Security Dashboard

- **Webhook Monitor**: Zeigt alle eingehenden Webhooks
- **API Health Monitor**: Status aller Integrationen
- **Systemstatus**: Gesamtübersicht

## 🔧 Häufige Probleme

### "Keine Anrufe kommen rein"
1. Prüfe Retell.ai Webhook URL
2. Verifiziere Webhook Secret
3. Teste mit "Anrufe abrufen" Button

### "Termine werden nicht erstellt"
1. Prüfe Cal.com API Key
2. Stelle sicher, dass Event Types verknüpft sind
3. Prüfe Verfügbarkeiten der Mitarbeiter

### "Webhook Errors"
1. Prüfe Webhook Signatures
2. Schaue in den Error Logs
3. Teste mit "Test All Webhooks" Button

## 📞 Support

Bei Problemen:
1. Prüfe zuerst den "Webhook Monitor"
2. Schaue in "API Health Monitor"
3. Überprüfe Logs in Grafana

## 🎯 Nächste Schritte

Nach erfolgreicher Einrichtung:
1. Führe mehrere Testanrufe durch
2. Prüfe die Terminbuchungen
3. Teste verschiedene Szenarien
4. Aktiviere E-Mail-Benachrichtigungen
5. Konfiguriere SMS-Benachrichtigungen (optional)

---

**Viel Erfolg mit AskProAI!** 🚀

Das System läuft bereits und wartet nur auf deine Konfiguration.