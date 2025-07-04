# 📋 Retell.ai Admin Guide - Wo kann ich was einstellen?

## 🎯 Hauptlösung: Retell Ultimate Control Center

**Link:** https://api.askproai.de/admin/retell-ultimate-control-center

### Was können Sie dort machen?
- ✅ **Agents verwalten** (anlegen, bearbeiten, löschen)
- ✅ **Custom Functions** erstellen und bearbeiten
- ✅ **Webhooks** konfigurieren
- ✅ **Phone Numbers** zuweisen
- ✅ **Performance** überwachen
- ✅ **Call History** einsehen
- ✅ **LLM Settings** anpassen
- ✅ **Voice Settings** konfigurieren

### Navigation im Control Center:
1. **Dashboard Tab**: Übersicht und Metriken
2. **Agents Tab**: Agent-Verwaltung und Konfiguration
3. **Functions Tab**: Custom AI Functions
4. **Webhooks Tab**: Webhook-Konfiguration
5. **Call History Tab**: Anrufverlauf
6. **Settings Tab**: Globale Einstellungen

---

## ❌ RetellConfigurationCenter (NUR Anzeige)

**Link:** https://api.askproai.de/admin/retell-configuration-center

### Was ist das Problem?
- Diese Page ist **READ-ONLY** (nur Anzeige)
- Keine Bearbeitungsfunktionen
- Nur Dashboard und Status-Anzeigen
- NICHT für Konfiguration geeignet

---

## 🔧 Alternative: Company Settings

**Link:** https://api.askproai.de/admin/companies/1/edit

### Navigation:
1. Tab "Kalender & Integration"
2. Sektion "🔗 Retell.ai Integration"

### Was können Sie dort einstellen?
- Retell API Key
- Standard-Agent auswählen (begrenzte Auswahl)
- Webhook URL (read-only)
- Basis-Einstellungen

---

## 📌 Empfehlung

**Verwenden Sie das Retell Ultimate Control Center** für alle Retell-Konfigurationen:

1. **Öffnen Sie:** https://api.askproai.de/admin/retell-ultimate-control-center
2. **Navigieren Sie** zum gewünschten Tab
3. **Bearbeiten Sie** Ihre Einstellungen direkt

### Wichtige Features im Control Center:
- **Agent Editor**: Klicken Sie auf einen Agent → "Edit" Button
- **Function Builder**: "Create Function" Button im Functions Tab
- **Webhook Test**: Testing Tab → "Test Webhook" Button
- **Import Agents**: "Import from Retell" Button

---

## 🚨 Häufige Probleme

### "Ich sehe keine Agents"
- Klicken Sie auf "Import from Retell" im Agents Tab
- Oder erstellen Sie einen neuen Agent mit "Create Agent"

### "Webhook funktioniert nicht"
1. Gehen Sie zum Webhooks Tab
2. Kopieren Sie die Webhook URL
3. Konfigurieren Sie diese in Retell.ai
4. Testen Sie mit "Test Webhook" Button

### "Telefonnummer wird nicht angezeigt"
- Phone Numbers Tab → "Assign Number"
- Wählen Sie Agent und Nummer aus

---

## 📞 Support

Bei Problemen mit der Konfiguration:
1. Screenshots vom Control Center machen
2. Fehlermeldungen notieren
3. Agent ID und Phone Number bereithalten