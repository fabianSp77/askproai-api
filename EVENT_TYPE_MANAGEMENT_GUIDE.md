# Event Type Management Guide

## Übersicht
Event Types sind die Basis für deine Dienstleistungen. Sie kommen aus Cal.com und definieren die verfügbaren Terminarten.

## Event Types verwalten

### 1. **Import aus Cal.com**
Im QuickSetupWizard:
- Button: "Cal.com Event Types importieren"
- Importiert alle aktiven Event Types aus Cal.com
- Erstellt automatisch passende Dienstleistungen

### 2. **Event Types anzeigen/bearbeiten**
- **Admin Panel**: `/admin/calcom-event-types`
- **QuickSetupWizard**: Button "Event Types verwalten" (öffnet in neuem Tab)
- Dort kannst du:
  - Event Types aktivieren/deaktivieren
  - Details bearbeiten
  - Event Types löschen (⚠️ VORSICHT!)

### 3. **Event Types entfernen - Best Practice**

#### ❌ **NICHT empfohlen: Löschen**
- Permanente Löschung
- Verlust historischer Daten
- Services verlieren Verknüpfung
- Keine Wiederherstellung möglich

#### ✅ **Empfohlen: Deaktivieren**
- Setze `is_active` auf false
- Event Type bleibt für Historie erhalten
- Kann jederzeit reaktiviert werden
- Services behalten Verknüpfung

### 4. **Automatische Synchronisation**

```bash
# Sync mit Cal.com (importiert neue, aktualisiert bestehende)
php artisan calcom:sync-event-types --company=1

# Sync + deaktiviere gelöschte Event Types
php artisan calcom:sync-event-types --company=1 --deactivate-missing

# Vorschau ohne Änderungen
php artisan calcom:sync-event-types --company=1 --deactivate-missing --dry-run
```

## Workflow für nicht mehr benötigte Event Types

### Option 1: In Cal.com löschen
1. Lösche Event Type in Cal.com
2. Führe Sync mit `--deactivate-missing` aus
3. Event Type wird lokal deaktiviert (nicht gelöscht)

### Option 2: Lokal deaktivieren
1. Gehe zu `/admin/calcom-event-types`
2. Bearbeite den Event Type
3. Setze "Aktiv" auf Nein
4. Event Type ist nicht mehr auswählbar

### Option 3: Service entfernen
1. Im QuickSetupWizard bei Dienstleistungen
2. Lösche die Dienstleistung (X-Button)
3. Event Type bleibt erhalten für andere Services

## Was passiert mit...

### **...bestehenden Terminen?**
- Bleiben unverändert
- Können weiter verwaltet werden
- Historie bleibt erhalten

### **...verknüpften Services?**
- Bei Deaktivierung: Services bleiben aktiv
- Bei Löschung: Services verlieren Verknüpfung
- Empfehlung: Services separat deaktivieren

### **...Mitarbeiter-Zuordnungen?**
- Bei Deaktivierung: Bleiben erhalten
- Bei Löschung: Werden entfernt
- Mitarbeiter können Event Type nicht mehr hosten

## Tipps

1. **Regelmäßig synchronisieren**
   - Hält lokale Daten aktuell mit Cal.com
   - Erkennt gelöschte Event Types

2. **Vor dem Löschen prüfen**
   - Gibt es aktive Services mit diesem Event Type?
   - Gibt es zukünftige Termine?
   - Gibt es Mitarbeiter-Zuordnungen?

3. **Saisonale Event Types**
   - Deaktivieren statt löschen
   - Können für nächste Saison reaktiviert werden

4. **Test Event Types**
   - Nach Tests deaktivieren
   - Bleiben für Dokumentation erhalten