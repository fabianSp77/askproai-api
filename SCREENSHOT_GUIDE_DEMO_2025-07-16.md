# 📸 Screenshot Guide für Demo Fallback

**Zweck:** Offline-Backup falls Internet/Server ausfällt
**Benötigte Zeit:** 20-30 Minuten
**Tools:** Browser Screenshot Extension oder MacOS Screenshot (Cmd+Shift+4)

## 🎯 Kritische Screenshots (MUSS)

### 1. Admin Portal - Login
- URL: `https://api.askproai.de/admin`
- Zeigt: Professioneller Login Screen
- Filename: `01_admin_login.png`

### 2. Admin Dashboard mit Multi-Company Widget
- Nach Login als `demo@askproai.de`
- Zeigt: Top 5 Companies, Call-Statistiken
- **WICHTIG:** Widget muss sichtbar sein!
- Filename: `02_admin_dashboard_multicompany.png`

### 3. Kundenverwaltung Übersicht
- URL: `https://api.askproai.de/admin/kundenverwaltung`
- Zeigt: Liste aller verwalteten Companies
- Filter auf "Reseller" für TechPartner
- Filename: `03_kundenverwaltung_overview.png`

### 4. Einzelne Company Details
- Klick auf "TechPartner GmbH"
- Zeigt: Company Details, White-Label Settings
- Filename: `04_company_details_reseller.png`

### 5. Portal Switch Process
- Hover über "Portal öffnen" Button
- Screenshot mit Tooltip/Dropdown
- Filename: `05_portal_switch_button.png`

### 6. Client Portal - Dashboard
- Nach Switch zu "Praxis Dr. Schmidt"
- Zeigt: Kunde sieht nur eigene Daten
- Filename: `06_client_portal_dashboard.png`

### 7. Client Portal - Anrufe
- Calls-Seite des Kunden
- Zeigt: Liste der Anrufe mit Details
- Filename: `07_client_calls_list.png`

### 8. Guthaben Management
- Admin Portal → PrepaidBalances
- Zeigt: Guthaben aller Kunden
- Filename: `08_balance_management.png`

## 📋 Nice-to-Have Screenshots

### 9. Call Details
- Beispiel-Anruf mit Transkript öffnen
- Zeigt: AI-Qualität und Datenerfassung
- Filename: `09_call_details_transcript.png`

### 10. Appointment Creation
- Neuen Termin anlegen
- Zeigt: Integration und Workflow
- Filename: `10_appointment_creation.png`

### 11. System Health/Metrics
- Wenn verfügbar: Performance Metriken
- Zeigt: Stabilität und Skalierbarkeit
- Filename: `11_system_metrics.png`

## 🎨 Screenshot Best Practices

### Browser-Einstellungen:
1. **Zoom:** 100% (normal)
2. **Fenster:** Maximiert oder 1920x1080
3. **Bookmarks:** Ausblenden
4. **Extensions:** Deaktivieren/Ausblenden

### Daten-Hygiene:
- ✅ Nur Demo-Daten zeigen
- ✅ Professionelle Firmennamen
- ❌ Keine echten Kundendaten
- ❌ Keine Debug-Meldungen

### Visuelle Tipps:
- Browser-Cache leeren (Ctrl+F5)
- Dark Mode deaktivieren
- Notifications ausschalten
- Konsole schließen

## 📁 Organisation

### Ordnerstruktur:
```
/demo-screenshots-2025-07-16/
├── 01_admin_login.png
├── 02_admin_dashboard_multicompany.png
├── 03_kundenverwaltung_overview.png
├── 04_company_details_reseller.png
├── 05_portal_switch_button.png
├── 06_client_portal_dashboard.png
├── 07_client_calls_list.png
├── 08_balance_management.png
├── 09_call_details_transcript.png
├── 10_appointment_creation.png
└── 11_system_metrics.png
```

### Backup:
- Lokal speichern
- Cloud-Backup (Google Drive/Dropbox)
- USB-Stick für Vor-Ort

## 🚀 Quick Screenshot Script

```bash
# MacOS Terminal Commands
mkdir -p ~/Desktop/demo-screenshots-2025-07-16
cd ~/Desktop/demo-screenshots-2025-07-16

# Öffne alle URLs in Tabs
open -a "Google Chrome" \
  "https://api.askproai.de/admin" \
  "https://api.askproai.de/admin/kundenverwaltung" \
  "https://api.askproai.de/business"

# Screenshot Tool starten
# MacOS: Cmd+Shift+5 für Screenshot-Toolbar
```

## 💡 Präsentations-Tipps

### Bei Screenshot-Präsentation:
1. **Vollbild-Modus** (F11)
2. **Langsam durchgehen**
3. **Auf Details hinweisen**
4. **Story erzählen**

### Übergangs-Phrasen:
- "Hier sehen Sie..."
- "Besonders wichtig ist..."
- "Das ermöglicht Ihnen..."
- "In der Praxis bedeutet das..."

## ⚡ Notfall-Plan

Falls Screenshots nicht reichen:
1. **Mockup-Tool**: Figma/Sketch Designs zeigen
2. **Architektur-Diagramm**: Technische Tiefe
3. **Excel-Kalkulation**: ROI demonstrieren
4. **Referenzen**: Andere erfolgreiche Reseller

---

**Hinweis:** Screenshots direkt nach Erstellung testen! Präsentations-Software (PowerPoint/Keynote) vorbereiten falls Browser nicht verfügbar.