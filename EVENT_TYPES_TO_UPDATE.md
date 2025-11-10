# Cal.com Event Types - Round Robin Konfiguration

**Datum:** 2025-11-04
**Problem:** Doppelte Buchungen durch "Kollektiv" Scheduling
**Lösung:** Umstellung auf "Round Robin" mit "Verfügbarkeit maximieren"

---

## ✅ Bereits konfiguriert

| Service | Event Type ID | Status |
|---------|---------------|--------|
| Herrenhaarschnitt | 3757770 | ✅ Round Robin aktiv |

---

## 🔧 Müssen noch konfiguriert werden

Für jeden dieser Event Types in Cal.com Web-UI:
1. Event Type öffnen
2. Tab: **Team** → **Zuordnung**
3. Umstellen auf: **Round Robin**
4. Verteilung: **Verfügbarkeit maximieren**
5. Speichern

### Haupt-Services (Priorität: HOCH)

| ID | Service Name | Cal.com Event Type ID |
|----|--------------|----------------------|
| 436 | Damenhaarschnitt | **3757757** |
| 434 | Kinderhaarschnitt | **3757772** |
| 439 | Waschen, schneiden, föhnen | **3757810** |
| 437 | Waschen & Styling | **3757809** |

### Weitere Services (Priorität: MITTEL)

| ID | Service Name | Cal.com Event Type ID |
|----|--------------|----------------------|
| 431 | Föhnen & Styling Damen | **3757762** |
| 430 | Föhnen & Styling Herren | **3757766** |
| 435 | Trockenschnitt | **3757808** |

### Färbe-Services (Priorität: MITTEL)

| ID | Service Name | Cal.com Event Type ID |
|----|--------------|----------------------|
| 440 | Ansatzfärbung | **3757707** |
| 442 | Ansatz + Längenausgleich | **3757697** |
| 444 | Komplette Umfärbung (Blondierung) | **3757773** |
| 443 | Balayage/Ombré | **3757710** |

### Spezial-Services (Priorität: NIEDRIG)

| ID | Service Name | Cal.com Event Type ID |
|----|--------------|----------------------|
| 441 | Dauerwelle | **3757758** |
| 432 | Gloss | **3757767** |
| 433 | Haarspende | **3757768** |
| 41 | Hairdetox | **3757769** |
| 42 | Intensiv Pflege Maria Nila | **3757771** |
| 43 | Rebuild Treatment Olaplex | **3757802** |

---

## 📝 Cal.com UI Navigation

**URL:** https://app.cal.com/event-types

**Schritte:**
1. Event Type aus Liste auswählen
2. Einstellungen öffnen
3. Tab: **Team** → Abschnitt: **Zuordnung**
4. **Termintyp:** `Round Robin` (statt "Kollektiv")
5. **Verteilung:** `Verfügbarkeit maximieren` aktivieren
6. Alle anderen Optionen deaktiviert lassen
7. **Speichern**

---

## ⚠️ Wichtig

**Warum Round Robin?**
- Verhindert doppelte Buchungen
- Nur 1 Mitarbeiter wird pro Termin zugeordnet
- Automatische Lastverteilung

**Was ist "Kollektiv"?**
- Alle Team-Mitglieder werden GLEICHZEITIG gebucht
- Führt zu doppelten Terminen im Kalender
- ❌ Nicht geeignet für Friseur-Termine

---

## ✅ Nach der Konfiguration

Testen mit:
```bash
php /var/www/api-gateway/scripts/test_calcom_full_flow.php
```

Erwartetes Ergebnis:
- ✅ Nur 1 Termin im Kalender
- ✅ 1 Mitarbeiter zugeordnet
- ✅ Keine Duplikate
