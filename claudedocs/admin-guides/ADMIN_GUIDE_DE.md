# AskProAI Admin-Handbuch (Deutsch)

**Letzte Aktualisierung:** 2. Oktober 2025
**Version:** 1.0
**Zielgruppe:** Geschäftsinhaber, Manager, Administratoren

---

## Inhaltsverzeichnis

1. [Einführung](#einführung)
2. [Rückruf-Verwaltung](#rückruf-verwaltung)
3. [Richtlinien-Konfiguration](#richtlinien-konfiguration)
4. [Problembehandlung](#problembehandlung)
5. [Best Practices](#best-practices)

---

## Einführung

Willkommen zum AskProAI Admin-Handbuch. Dieses Dokument hilft Ihnen, die wichtigsten Verwaltungsfunktionen des Termin-Verwaltungssystems zu verstehen und optimal zu nutzen.

### Was ist AskProAI?

AskProAI ist ein intelligentes Termin-Verwaltungssystem, das:
- Automatische Terminbuchung über Cal.com ermöglicht
- KI-gestützte Sprachanrufe mit Retell AI verarbeitet
- Rückrufanfragen intelligent verwaltet und zuweist
- Flexible Stornierungsrichtlinien mit Gebühren unterstützt

---

## Rückruf-Verwaltung

### 📋 Was ist Rückruf-Verwaltung?

Wenn ein Kunde anruft, aber kein sofortiger Termin verfügbar ist, wird eine **Rückrufanfrage** erstellt. Das System verwaltet diese Anfragen automatisch und stellt sicher, dass kein Kunde vergessen wird.

### Wie funktioniert die automatische Zuweisung?

Das System weist Rückrufanfragen intelligent Ihren Mitarbeitern zu:

#### Zuweisungs-Strategie

1. **Bevorzugter Mitarbeiter**
   - Wenn der Kunde einen bestimmten Mitarbeiter wünscht, wird dieser zuerst geprüft
   - Der Mitarbeiter muss aktiv sein, um zugewiesen zu werden

2. **Service-Experte**
   - Mitarbeiter, die für den angefragten Service qualifiziert sind
   - System wählt den Experten mit der geringsten aktuellen Arbeitslast

3. **Geringste Auslastung**
   - Falls kein Experte verfügbar ist, wird der Mitarbeiter mit den wenigsten offenen Rückrufen gewählt
   - Dies verteilt die Arbeitslast gleichmäßig im Team

#### Prioritäten

Das System kennt drei Prioritätsstufen:

| Priorität | Ablaufzeit | Automatische Zuweisung | Verwendung |
|-----------|------------|------------------------|------------|
| **Normal** | 24 Stunden | Ja (wenn konfiguriert) | Standard-Anfragen |
| **Hoch** | 4 Stunden | Immer | Wichtige Kunden |
| **Dringend** | 2 Stunden | Immer | Notfälle, VIP-Kunden |

### Status-Übersicht

Jede Rückrufanfrage durchläuft verschiedene Status:

```
Ausstehend → Zugewiesen → Kontaktiert → Abgeschlossen
     ↓
  Abgelaufen (wenn nicht rechtzeitig bearbeitet)
     ↓
  Eskaliert (zu anderem Mitarbeiter)
```

#### Status-Bedeutungen

- **Ausstehend**: Neu erstellt, wartet auf Bearbeitung
- **Zugewiesen**: Einem Mitarbeiter zugewiesen, der kontaktieren soll
- **Kontaktiert**: Mitarbeiter hat Kunde bereits angerufen
- **Abgeschlossen**: Erfolgreich bearbeitet, Termin gebucht oder Problem gelöst
- **Abgelaufen**: Zeitlimit überschritten ohne Bearbeitung
- **Abgebrochen**: Kunde hat Anfrage zurückgezogen

### Eskalations-Regeln

Wenn ein Rückruf nicht rechtzeitig bearbeitet wird, eskaliert das System automatisch:

#### Wann wird eskaliert?

- Rückruf ist **überfällig** (Ablaufzeit überschritten)
- Kein Mitarbeiter verfügbar zur ursprünglichen Zuweisungszeit
- Ursprünglich zugewiesener Mitarbeiter antwortet nicht

#### Was passiert bei Eskalation?

1. System findet einen anderen verfügbaren Mitarbeiter in der Filiale
2. Rückruf wird dem neuen Mitarbeiter zugewiesen
3. Eskalations-Ereignis wird protokolliert mit:
   - Grund der Eskalation
   - Ursprünglicher Mitarbeiter
   - Neuer Mitarbeiter
   - Zeitstempel

### Rückrufanfragen im Admin-Panel verwalten

#### Ansicht öffnen

1. Melden Sie sich im Filament Admin-Panel an
2. Navigieren Sie zu **Termine → Rückrufe**
3. Sie sehen eine Tabelle aller Rückrufanfragen

#### Filter verwenden

Filtern Sie Rückrufe nach:
- **Status**: Zeigen Sie nur ausstehende, zugewiesene oder abgeschlossene Anfragen
- **Priorität**: Fokus auf dringende oder hochpriorisierte Rückrufe
- **Filiale**: Wenn Sie mehrere Standorte haben
- **Zeitraum**: Heute, diese Woche, diesen Monat

#### Rückruf manuell zuweisen

1. Klicken Sie auf die Rückrufanfrage
2. Wählen Sie **Zuweisen zu Mitarbeiter**
3. Wählen Sie den gewünschten Mitarbeiter aus der Liste
4. Klicken Sie **Speichern**

Das System benachrichtigt den Mitarbeiter über die neue Zuweisung.

#### Status manuell ändern

1. Öffnen Sie die Rückrufanfrage
2. Klicken Sie **Status ändern**
3. Wählen Sie den neuen Status:
   - **Kontaktiert**: Wenn Mitarbeiter Kunde erreicht hat
   - **Abgeschlossen**: Wenn Problem gelöst ist
   - **Abgebrochen**: Wenn nicht mehr relevant
4. Fügen Sie Notizen hinzu (empfohlen)
5. Klicken Sie **Speichern**

### Häufige Szenarien

#### ✅ Szenario 1: Kunde ruft außerhalb der Geschäftszeiten an

**Was passiert:**
- Retell AI-Agent nimmt Anruf entgegen
- Erkennt keine verfügbaren Termine
- Erstellt Rückrufanfrage mit "Normal"-Priorität
- System weist automatisch zu, wenn Geschäftszeiten beginnen

**Was Sie tun sollten:**
- Überprüfen Sie morgens die ausstehenden Rückrufe
- Kontaktieren Sie Kunden innerhalb der Ablaufzeit
- Markieren Sie als "Abgeschlossen" nach Terminbuchung

#### ✅ Szenario 2: VIP-Kunde benötigt dringenden Termin

**Was passiert:**
- Rückrufanfrage wird mit "Dringend"-Priorität erstellt
- System weist sofort zu (auch außerhalb normaler Zeiten)
- Ablaufzeit: 2 Stunden

**Was Sie tun sollten:**
- Reagieren Sie sofort auf dringende Rückrufe
- Versuchen Sie, Termine umzuorganisieren, falls möglich
- Dokumentieren Sie Ergebnis in Notizen

#### ✅ Szenario 3: Mitarbeiter ist krank, kann Rückruf nicht bearbeiten

**Was Sie tun können:**
1. Öffnen Sie überfällige Rückrufe
2. Klicken Sie auf "Neu zuweisen"
3. Wählen Sie verfügbaren Kollegen
4. System aktualisiert Zuweisung automatisch

**Alternative:**
- Warten Sie, bis System automatisch eskaliert (nach Ablaufzeit)
- System findet automatisch anderen Mitarbeiter

### Best Practices für Rückruf-Verwaltung

1. **Reagieren Sie schnell**
   - Kontaktieren Sie Kunden innerhalb der Prioritäts-Zeitlimits
   - Dringende Anfragen haben Vorrang

2. **Dokumentieren Sie alles**
   - Fügen Sie Notizen zu jedem Rückruf hinzu
   - Notieren Sie Gesprächsergebnisse, Kundenwünsche

3. **Überprüfen Sie täglich**
   - Checken Sie morgens ausstehende Rückrufe
   - Identifizieren Sie überfällige Anfragen

4. **Nutzen Sie Prioritäten**
   - Setzen Sie "Dringend" nur für echte Notfälle
   - "Hoch" für wichtige Kunden oder zeitkritische Anfragen
   - "Normal" für Standard-Anfragen

5. **Schulen Sie Ihr Team**
   - Stellen Sie sicher, dass alle Mitarbeiter das System kennen
   - Erklären Sie die Wichtigkeit schneller Reaktion

---

## Richtlinien-Konfiguration

### 📋 Verständnis von Stornierungsrichtlinien

Stornierungsrichtlinien legen fest:
- **Wie lange vorher** Kunden stornieren können
- **Wie oft** Kunden pro Monat stornieren dürfen
- **Welche Gebühren** bei kurzfristiger Stornierung anfallen

### Richtlinien-Hierarchie

AskProAI verwendet ein hierarchisches System für Richtlinien. Das bedeutet: Spezifischere Einstellungen überschreiben allgemeine Einstellungen.

#### Hierarchie-Reihenfolge (von spezifisch zu allgemein)

```
1. Mitarbeiter (höchste Priorität)
   ↓
2. Service
   ↓
3. Filiale
   ↓
4. Unternehmen (niedrigste Priorität)
```

#### Beispiel: Wie Hierarchie funktioniert

**Ihre Konfiguration:**
- **Unternehmen**: Stornierung 24 Stunden vorher, Gebühr 10€
- **Filiale Berlin**: Stornierung 48 Stunden vorher, Gebühr 15€
- **Service "Haare färben"**: Stornierung 72 Stunden vorher, Gebühr 25€

**Ergebnis:**
- Termin für "Haarschnitt" in Berlin: 48h vorher, 15€ (Filiale-Richtlinie)
- Termin für "Haare färben" in Berlin: 72h vorher, 25€ (Service-Richtlinie überschreibt)
- Termin für "Haarschnitt" in Hamburg (keine Filiale-Richtlinie): 24h vorher, 10€ (Unternehmens-Richtlinie)

### Richtlinien-Typen

#### 1. Stornierungsrichtlinie (cancellation)

Regelt, wie Kunden Termine stornieren können.

**Wichtige Parameter:**

| Parameter | Beschreibung | Beispiel |
|-----------|--------------|----------|
| `hours_before` | Mindeststunden vor Termin für Stornierung | `24` (24 Stunden) |
| `max_cancellations_per_month` | Maximale Stornierungen pro Kunde pro Monat | `3` |
| `fee_tiers` | Gestaffelte Gebühren nach Vorlaufzeit | Siehe unten |

**Gebühren-Staffelung (fee_tiers):**

```json
"fee_tiers": [
  {
    "min_hours": 48,
    "fee": 0.0
  },
  {
    "min_hours": 24,
    "fee": 10.0
  },
  {
    "min_hours": 0,
    "fee": 15.0
  }
]
```

**Bedeutung:**
- **≥ 48 Stunden vorher**: Keine Gebühr (0€)
- **24-48 Stunden vorher**: 10€ Gebühr
- **< 24 Stunden vorher**: 15€ Gebühr

#### 2. Umbuchungsrichtlinie (reschedule)

Regelt, wie Kunden Termine verschieben können.

**Wichtige Parameter:**

| Parameter | Beschreibung | Beispiel |
|-----------|--------------|----------|
| `hours_before` | Mindeststunden vor Termin für Umbuchung | `12` |
| `max_reschedules_per_appointment` | Wie oft ein einzelner Termin verschoben werden darf | `2` |
| `fee_tiers` | Gestaffelte Gebühren | Wie bei Stornierung |

### Richtlinien konfigurieren

#### Unternehmens-Richtlinie erstellen (gilt für alle)

1. Navigieren Sie zu **Einstellungen → Richtlinien**
2. Wählen Sie **Neue Richtlinie**
3. Wählen Sie **Typ**: Unternehmen
4. Wählen Sie Ihr Unternehmen aus der Liste
5. Wählen Sie **Richtlinientyp**: Stornierung oder Umbuchung

**Beispiel-Konfiguration für Arztpraxis:**

```
Richtlinientyp: Stornierung
Stunden vorher: 24
Max. Stornierungen pro Monat: 2

Gebühren-Staffelung:
- ≥ 48h vorher: 0€
- 24-48h vorher: 20€
- < 24h vorher: 30€
```

**Speichern** Sie die Richtlinie.

#### Filiale-Richtlinie erstellen (überschreibt Unternehmens-Richtlinie)

1. Navigieren Sie zu **Filialen → Ihre Filiale → Richtlinien**
2. Klicken Sie **Neue Richtlinie**
3. Konfigurieren Sie spezifische Einstellungen für diese Filiale

**Beispiel: Innenstadtfiliale mit höherer Nachfrage**

```
Richtlinientyp: Stornierung
Stunden vorher: 48 (strenger als Unternehmens-Richtlinie)
Max. Stornierungen pro Monat: 1 (strenger)

Gebühren:
- ≥ 72h vorher: 0€
- 48-72h vorher: 15€
- < 48h vorher: 40€
```

#### Service-Richtlinie erstellen (überschreibt Filialen- und Unternehmens-Richtlinie)

1. Navigieren Sie zu **Services → Ihr Service → Richtlinien**
2. Klicken Sie **Neue Richtlinie**
3. Konfigurieren Sie service-spezifische Einstellungen

**Beispiel: Friseur - Färbebehandlung (teuer, zeitintensiv)**

```
Richtlinientyp: Stornierung
Stunden vorher: 72 (3 Tage)
Max. Stornierungen pro Monat: 1

Gebühren:
- ≥ 72h vorher: 0€
- 48-72h vorher: 50€
- < 48h vorher: 100% des Servicepreises
```

Für prozentuale Gebühren:
```
Fee-Prozentsatz: 100
(System berechnet automatisch basierend auf Servicepreis)
```

### Gebühren-Konfiguration im Detail

#### Feste Gebühren

Einfachste Methode: Eine feste Gebühr für alle Stornierungen/Umbuchungen.

```json
{
  "hours_before": 24,
  "fee": 15.0
}
```

**Verwendung:** Kleine Unternehmen, einfache Services

#### Gestaffelte Gebühren (empfohlen)

Flexibel: Unterschiedliche Gebühren je nach Vorlaufzeit.

```json
{
  "hours_before": 24,
  "fee_tiers": [
    { "min_hours": 72, "fee": 0.0 },
    { "min_hours": 48, "fee": 10.0 },
    { "min_hours": 24, "fee": 20.0 },
    { "min_hours": 0, "fee": 30.0 }
  ]
}
```

**Verwendung:** Mittlere bis große Unternehmen, faire Abstufung

#### Prozentuale Gebühren

Gebühr basierend auf Servicepreis.

```json
{
  "hours_before": 48,
  "fee_percentage": 50
}
```

**Beispiel:**
- Service kostet 80€
- Kunde storniert < 48h vorher
- Gebühr: 40€ (50% von 80€)

**Verwendung:** Hochpreisige Services (Kosmetik, Beratung)

### Beispiele für verschiedene Branchen

#### 🏥 Arztpraxis

```
Stornierungsrichtlinie:
- Stunden vorher: 24
- Max. Stornierungen/Monat: 2
- Gebühren:
  - ≥ 24h: 0€
  - < 24h: 25€ (Ausfallgebühr)

Umbuchungsrichtlinie:
- Stunden vorher: 12
- Max. Umbuchungen pro Termin: 2
- Gebühr: 0€ (keine Gebühr für Umbuchung)
```

**Begründung:**
- 24h Vorlaufzeit ermöglicht Nachbesetzung
- Gebühr nur bei kurzfristiger Absage
- Umbuchung kostenlos (flexibel für Patienten)

#### 💇 Friseursalon

```
Stornierungsrichtlinie (Standard):
- Stunden vorher: 24
- Max. Stornierungen/Monat: 3
- Gebühren:
  - ≥ 48h: 0€
  - 24-48h: 10€
  - < 24h: 15€

Service "Färbebehandlung":
- Stunden vorher: 72
- Max. Stornierungen/Monat: 1
- Gebühren:
  - ≥ 72h: 0€
  - 48-72h: 30€
  - < 48h: 50€
```

**Begründung:**
- Standard-Services: Moderate Richtlinie
- Spezial-Services: Strenger (Material wird vorbereitet)
- Gestaffelte Gebühren ermutigen frühe Stornierung

#### 💼 Unternehmensberatung

```
Stornierungsrichtlinie:
- Stunden vorher: 48
- Max. Stornierungen/Monat: 1
- Gebühren: 50% des Beratungshonorars

Umbuchungsrichtlinie:
- Stunden vorher: 24
- Max. Umbuchungen pro Termin: 1
- Gebühr: 0€
```

**Begründung:**
- Lange Vorlaufzeit (Berater-Zeit ist wertvoll)
- Prozentuale Gebühr fair bei unterschiedlichen Preisen
- Eine kostenlose Umbuchung (Flexibilität)

#### 🍽️ Restaurant (Tischreservierung)

```
Stornierungsrichtlinie:
- Stunden vorher: 4
- Max. Stornierungen/Monat: unbegrenzt
- Gebühren: 0€

Großgruppen (>6 Personen):
- Stunden vorher: 24
- Gebühr: 20€ pro Person
```

**Begründung:**
- Kurze Vorlaufzeit (Tische können schnell neu vergeben werden)
- Strenger bei Großgruppen (höherer Aufwand)

### Richtlinien testen

⚠️ **Wichtig:** Testen Sie neue Richtlinien, bevor Sie sie aktivieren!

#### Test-Szenario durchspielen

1. Erstellen Sie Test-Termin in Ihrem System
2. Versuchen Sie Stornierung zu verschiedenen Zeiten:
   - 5 Tage vorher
   - 2 Tage vorher
   - 12 Stunden vorher
3. Überprüfen Sie berechnete Gebühren
4. Passen Sie Richtlinie an, falls nötig

#### Mitarbeiter schulen

Bevor Sie neue Richtlinien aktivieren:
1. Informieren Sie Ihr Team über Änderungen
2. Erklären Sie Begründung der Richtlinien
3. Üben Sie Umgang mit Kunden-Anfragen
4. Bereiten Sie FAQ für häufige Fragen vor

---

## Problembehandlung

### ⚠️ Häufige Probleme und Lösungen

#### Problem 1: Rückrufe werden nicht automatisch zugewiesen

**Symptome:**
- Rückrufanfragen bleiben im Status "Ausstehend"
- Keine automatische Zuweisung an Mitarbeiter

**Mögliche Ursachen und Lösungen:**

✅ **Lösung 1: Auto-Zuweisung aktivieren**
1. Gehen Sie zu **Einstellungen → Rückrufe**
2. Prüfen Sie: **Auto-Zuweisung aktiviert** = Ja
3. Speichern Sie die Einstellung

✅ **Lösung 2: Mitarbeiter-Verfügbarkeit prüfen**
1. Navigieren Sie zu **Mitarbeiter**
2. Prüfen Sie für jeden Mitarbeiter:
   - **Status**: Muss "Aktiv" sein
   - **Arbeitszeiten**: Müssen konfiguriert sein
3. Aktualisieren Sie inaktive Mitarbeiter

✅ **Lösung 3: Filiale-Zuweisung prüfen**
1. Öffnen Sie **Mitarbeiter**
2. Stellen Sie sicher: Mitarbeiter sind der richtigen **Filiale** zugeordnet
3. Rückrufe werden nur an Mitarbeiter derselben Filiale zugewiesen

**Wie Sie prüfen, ob es funktioniert:**
- Erstellen Sie Test-Rückrufanfrage
- Prüfen Sie nach 1-2 Minuten: Status sollte "Zugewiesen" sein
- Prüfen Sie zugewiesenen Mitarbeiter

#### Problem 2: Richtlinie wird nicht korrekt angewendet

**Symptome:**
- Falsche Gebühren berechnet
- Stornierung trotz Richtlinie erlaubt/verweigert
- Unerwartetes Verhalten bei Umbuchung

**Mögliche Ursachen und Lösungen:**

✅ **Lösung 1: Hierarchie prüfen**

Erinnern Sie sich: Spezifische Richtlinien überschreiben allgemeine!

1. Prüfen Sie alle Richtlinien-Ebenen:
   - Mitarbeiter-Richtlinie (höchste Priorität)
   - Service-Richtlinie
   - Filiale-Richtlinie
   - Unternehmens-Richtlinie (niedrigste Priorität)

2. Identifizieren Sie, welche Richtlinie greift
3. Passen Sie die **richtige Ebene** an

**Beispiel:**
```
Problem: Service "Massage" sollte 48h Vorlaufzeit haben, aber 24h wird akzeptiert

Prüfen Sie:
- Service "Massage" → Richtlinien: hours_before = ?
- Filiale → Richtlinien: hours_before = 24 (Dies überschreibt!)

Lösung: Service-Richtlinie mit "is_override" = true erstellen
```

✅ **Lösung 2: Cache leeren**

Richtlinien werden gecached für Performance. Nach Änderungen:

```bash
# Im Terminal auf Ihrem Server:
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
```

Oder im Admin-Panel:
1. **Einstellungen → System → Cache**
2. Klicken Sie **Cache leeren**

✅ **Lösung 3: Richtlinien-Konfiguration validieren**

Prüfen Sie JSON-Syntax:

```json
# ✅ RICHTIG
{
  "hours_before": 24,
  "max_cancellations_per_month": 3,
  "fee_tiers": [
    { "min_hours": 48, "fee": 0.0 },
    { "min_hours": 24, "fee": 10.0 }
  ]
}

# ❌ FALSCH (Komma-Fehler)
{
  "hours_before": 24,
  "max_cancellations_per_month": 3,
  "fee_tiers": [
    { "min_hours": 48, "fee": 0.0 }
    { "min_hours": 24, "fee": 10.0 }  # Fehlendes Komma!
  ]
}
```

Verwenden Sie einen JSON-Validator: https://jsonlint.com

#### Problem 3: Gebühren werden nicht berechnet

**Symptome:**
- Stornierung/Umbuchung zeigt 0€ Gebühr, obwohl Richtlinie Gebühr vorsieht
- Kunde sieht keine Gebühren-Information

**Mögliche Ursachen und Lösungen:**

✅ **Lösung 1: Prüfen Sie Vorlaufzeit**

```
Richtlinie: hours_before = 24, fee = 10€
Termin: Morgen 10:00 Uhr
Jetzt: Heute 9:00 Uhr
Vorlaufzeit: 25 Stunden

→ Stornierung erlaubt, KEINE Gebühr (>24h)
```

System ist korrekt! Gebühr gilt nur bei kurzfristigerer Stornierung.

✅ **Lösung 2: Prüfen Sie fee_tiers Reihenfolge**

Gebühren-Staffelung muss von **hoch nach niedrig** sortiert sein:

```json
# ✅ RICHTIG
"fee_tiers": [
  { "min_hours": 72, "fee": 0.0 },
  { "min_hours": 48, "fee": 10.0 },
  { "min_hours": 24, "fee": 20.0 },
  { "min_hours": 0, "fee": 30.0 }
]

# ❌ FALSCH (falsche Reihenfolge)
"fee_tiers": [
  { "min_hours": 0, "fee": 30.0 },
  { "min_hours": 24, "fee": 20.0 }
]
```

✅ **Lösung 3: Prüfen Sie Termin-Preis (bei Prozent-Gebühr)**

```
Richtlinie: fee_percentage = 50
Termin-Preis: NULL oder 0€

→ Gebühr = 0€ (50% von 0€)
```

Stellen Sie sicher, dass Services korrekte Preise haben:
1. **Services → Ihr Service**
2. Prüfen Sie Feld **Preis**
3. Aktualisieren Sie, falls leer oder 0

#### Problem 4: Cal.com Integration funktioniert nicht

**Symptome:**
- Termine werden nicht in Cal.com erstellt
- Verfügbarkeiten werden nicht geladen
- Fehler bei Terminbuchung

**Mögliche Ursachen und Lösungen:**

✅ **Lösung 1: API-Verbindung prüfen**

1. Gehen Sie zu **Einstellungen → Integrationen → Cal.com**
2. Prüfen Sie:
   - **API-Key**: Muss gültig sein
   - **Event Type ID**: Muss existieren
   - **Status**: Muss "Verbunden" zeigen
3. Testen Sie Verbindung mit **Test-Button**

✅ **Lösung 2: Event Type existiert in Cal.com**

1. Melden Sie sich bei Cal.com an (https://cal.com)
2. Gehen Sie zu **Event Types**
3. Prüfen Sie: Event Type ID aus AskProAI existiert
4. Stellen Sie sicher: Event Type ist **aktiv**

✅ **Lösung 3: API-Rate-Limits**

Cal.com hat Limits für API-Anfragen:
- **Basic**: 100 Anfragen/Stunde
- **Pro**: 1000 Anfragen/Stunde

Wenn Limit erreicht:
- Warten Sie 1 Stunde
- Upgraden Sie Cal.com Plan
- Implementieren Sie Anfrage-Batching

**Prüfen in Logs:**
```bash
# Auf Ihrem Server:
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Cal.com"
```

Suchen Sie nach "Rate limit exceeded" oder "429" Fehlern.

### 📊 Logs überprüfen

Wenn Probleme auftreten, sind Logs Ihre beste Informationsquelle.

#### Im Admin-Panel

1. Navigieren Sie zu **System → Logs**
2. Filtern Sie nach:
   - **Zeitraum**: Letzten Stunden/Tage
   - **Level**: Error, Warning
   - **Kategorie**: Callbacks, Policies, Appointments
3. Suchen Sie relevante Einträge

#### Auf dem Server (für IT-Personal)

```bash
# Haupt-Log
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Nur Fehler
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep ERROR

# Rückruf-bezogene Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Callback"

# Richtlinien-bezogene Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Policy"
```

#### Log-Einträge verstehen

**Erfolgreiche Aktionen:**
```
✅ Created callback request | callback_id: 123 | customer_name: Max Müller
📋 Callback assigned to staff | staff_name: Anna Schmidt
```

**Warnungen:**
```
⚠️ No staff available for auto-assignment | branch_id: 5
⚠️ Callback escalated | reason: overdue
```

**Fehler:**
```
❌ Failed to assign callback | error: Staff not found
❌ Policy calculation error | error: Invalid configuration
```

### 🆘 Wann sollten Sie den Support kontaktieren?

Kontaktieren Sie Support, wenn:

1. **Systemweite Fehler**
   - Alle Benutzer betroffen
   - Admin-Panel nicht erreichbar
   - Keine Termine buchbar

2. **Daten-Probleme**
   - Termine verschwinden
   - Kundendaten inkorrekt
   - Finanzielle Transaktionen falsch

3. **Nach Troubleshooting**
   - Sie haben alle Lösungen versucht
   - Problem besteht nach 24 Stunden
   - Logs zeigen unbekannte Fehler

4. **Kritische Geschäfts-Auswirkungen**
   - Kundenbeschwerden häufen sich
   - Umsatzverlust droht
   - Rechtliche Bedenken

**Was Sie bereitstellen sollten:**

1. **Problem-Beschreibung**
   - Was passiert?
   - Was sollte passieren?
   - Seit wann tritt Problem auf?

2. **Schritte zur Reproduktion**
   - Wie kann Support das Problem nachvollziehen?
   - Welche Aktionen führen zum Fehler?

3. **Screenshots**
   - Fehlermeldungen
   - Relevante Admin-Panel-Ansichten
   - Log-Einträge

4. **System-Informationen**
   - Ihr Unternehmens-Name/ID
   - Betroffene Filiale(n)
   - Browser/Gerät (falls relevant)

**Support-Kontakt:**

- **E-Mail**: support@askproai.com
- **Telefon**: +49 (0) XXX XXXXXXX
- **Support-Portal**: https://support.askproai.com
- **Notfall-Hotline** (24/7): +49 (0) XXX XXXXXXX

---

## Best Practices

### 📝 Allgemeine Empfehlungen

#### 1. Regelmäßige Überprüfung

**Täglich:**
- Überprüfen Sie ausstehende Rückrufe
- Bearbeiten Sie überfällige Anfragen
- Prüfen Sie Termin-Stornierungen

**Wöchentlich:**
- Analysieren Sie Rückruf-Statistiken
- Überprüfen Sie Mitarbeiter-Auslastung
- Kontrollieren Sie Richtlinien-Einhaltung

**Monatlich:**
- Bewerten Sie Richtlinien-Effektivität
- Analysieren Sie Gebühren-Einnahmen
- Schulen Sie Team bei Bedarf

#### 2. Richtlinien-Management

**Start konservativ:**
- Beginnen Sie mit moderaten Richtlinien
- Sammeln Sie Feedback von Kunden und Team
- Passen Sie schrittweise an

**Transparenz:**
- Kommunizieren Sie Richtlinien klar
- Zeigen Sie sie auf Website und in Bestätigungs-E-Mails
- Erklären Sie Begründung bei Nachfrage

**Flexibilität:**
- Ermöglichen Sie Ausnahmen in begründeten Fällen
- Dokumentieren Sie Ausnahmen
- Nutzen Sie manuelles Überschreiben, wenn nötig

#### 3. Team-Schulung

**Onboarding für neue Mitarbeiter:**
1. Einführung in Admin-Panel
2. Erklärung Rückruf-Workflow
3. Training Richtlinien-Kommunikation
4. Praktische Übungen mit Test-Daten

**Kontinuierliches Training:**
- Monatliche Team-Meetings
- Teilen Sie Best Practices
- Diskutieren Sie schwierige Fälle
- Update bei System-Änderungen

#### 4. Kunden-Kommunikation

**Bei Rückrufen:**
- Antworten Sie innerhalb der Prioritäts-Zeitlimits
- Seien Sie freundlich und lösungsorientiert
- Bieten Sie mehrere Termin-Optionen an
- Bestätigen Sie gebuchten Termin schriftlich

**Bei Richtlinien:**
- Erklären Sie Richtlinien bei Buchung
- Senden Sie Erinnerungs-E-Mails mit Richtlinien
- Seien Sie empathisch bei Ausnahme-Anfragen
- Dokumentieren Sie Vereinbarungen

#### 5. Performance-Optimierung

**Metriken überwachen:**
- **Rückruf-Bearbeitungszeit**: Durchschnittliche Zeit bis zur Kontaktierung
- **Erfolgsquote**: Prozentsatz abgeschlossener Rückrufe
- **Eskalations-Rate**: Wie oft werden Rückrufe eskaliert?
- **Stornierungsquote**: Prozentsatz Termine mit Stornierung

**Ziele setzen:**
```
Beispiel-Ziele:
- Durchschnittliche Rückruf-Bearbeitung: < 2 Stunden
- Erfolgsquote: > 90%
- Eskalations-Rate: < 5%
- Stornierungsquote: < 10%
```

**Kontinuierliche Verbesserung:**
1. Identifizieren Sie Engpässe
2. Testen Sie Verbesserungen
3. Messen Sie Auswirkungen
4. Skalieren Sie erfolgreiche Ansätze

### 🎯 Branchenspezifische Tipps

#### Gesundheitswesen (Arzt, Zahnarzt, Physiotherapie)

- **Strenge Richtlinien**: Ausfälle kosten viel
- **Reminder-System**: Automatische Erinnerungen 24h + 2h vorher
- **Notfall-Slots**: Reservieren Sie Slots für Notfälle
- **Dokumentation**: Gründe für Absagen dokumentieren (Versicherung)

#### Beauty & Wellness (Friseur, Kosmetik, Spa)

- **Service-spezifische Richtlinien**: Färben strenger als Schneiden
- **Material-Kosten**: Berücksichtigen Sie in Gebühren
- **Stamm-Kunden**: Kulanz bei langjährigen Kunden
- **Saisonale Anpassungen**: Strenger vor Feiertagen

#### Beratung & Coaching

- **Lange Vorlaufzeiten**: 48-72h Standard
- **Vorbereitungszeit**: Berücksichtigen Sie in Richtlinien
- **Flexibilität**: Ermöglichen Sie kostenlose Umbuchung
- **Paket-Buchungen**: Spezielle Regeln für Serien-Termine

#### Gastronomie

- **Kurze Vorlaufzeiten**: 4-6h ausreichend
- **Großgruppen**: Strengere Regeln ab 6+ Personen
- **Keine Gebühren**: Meist unüblich (außer No-Show)
- **Warteliste**: Nutzen Sie bei Absagen

---

## Abschluss

Dieses Handbuch bietet Ihnen die Grundlagen für effektives Management von Rückrufen und Richtlinien in AskProAI.

**Nächste Schritte:**

1. ✅ Lesen Sie relevante Abschnitte für Ihr Geschäft
2. ✅ Konfigurieren Sie Ihre ersten Richtlinien
3. ✅ Schulen Sie Ihr Team
4. ✅ Testen Sie den Workflow
5. ✅ Sammeln Sie Feedback und optimieren Sie

**Hilfe & Ressourcen:**

- 📚 **Vollständige Dokumentation**: https://docs.askproai.com
- 🎥 **Video-Tutorials**: https://askproai.com/tutorials
- 💬 **Community-Forum**: https://community.askproai.com
- 📧 **Support**: support@askproai.com

---

**Viel Erfolg mit AskProAI!** 🚀

*Letzte Aktualisierung: 2. Oktober 2025 | Version 1.0*
