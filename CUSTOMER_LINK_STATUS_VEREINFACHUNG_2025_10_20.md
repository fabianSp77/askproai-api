# customer_link_status Vereinfachung - 2025-10-20

## ❓ User-Frage

> "Wo ist der Unterschied zwischen nur Name und nicht verknüpft ich verstehe diese Logik nicht"

**Antwort**: Du hast Recht - es ist verwirrend! "anonymous" und "unlinked" sind **praktisch identisch**.

---

## 📊 Aktuelle Situation (VERWIRREND)

### Status-Verteilung

| Status | Count | customer_name | customer_id | Badge | Problem |
|--------|-------|---------------|-------------|-------|---------|
| **linked** | 50 | 18 haben | 50 haben | "✓ Verknüpft" | ✅ Klar |
| **name_only** | 53 | 44 haben | 0 haben | "⚠ Nur Name" | ✅ Klar |
| **anonymous** | 68 | 0 haben | 0 haben | "👤 Anonym" | ❓ |
| **unlinked** | 6 | 0 haben | 0 haben | "○ Nicht verknüpft" | ❓ |

### Problem: anonymous vs unlinked

**Beide haben**:
- ❌ Kein customer_name
- ❌ Kein customer_id
- ❌ Keine Kundendaten

**Einziger "Unterschied"**:
- anonymous: 44 mit from_number='anonymous', 24 mit anderen Nummern
- unlinked: 1 mit from_number='anonymous', 5 alte Test-Calls

**Fazit**: ❌ **KEIN sinnvoller Unterschied!**

---

## 🤔 Analyse der 6 "unlinked" Calls

```
ID 496, 497, 498, 499, 514: Test-Calls (in_progress, keine Duration)
ID 607: Normaler Call (completed, 45 sec)

Alle:
  - customer_name: NULL
  - customer_id: NULL
  - Status: Sollten eigentlich 'anonymous' sein!
```

**Fazit**: "unlinked" sind einfach Calls die nicht korrekt kategorisiert wurden.

---

## ✅ Vorgeschlagene Vereinfachung

### Option 1: 3 Klare Kategorien (EMPFOHLEN)

| Status | Bedingung | Badge | Bedeutung |
|--------|-----------|-------|-----------|
| **verified** | customer_id vorhanden | "✓ Kunde" (grün) | Mit Kundenprofil verknüpft |
| **name_known** | customer_name vorhanden, kein customer_id | "📝 Name bekannt" (orange) | Nur Name, kein Profil |
| **unknown** | Weder Name noch ID | "❓ Unbekannt" (grau) | Keine Kundendaten |

**Vorteile**:
- ✅ Nur 3 Kategorien (nicht 4)
- ✅ Klar unterscheidbar
- ✅ Keine Redundanz
- ✅ User-verständlich

---

### Option 2: Aktuelles System vereinfachen

**Aktion**: "unlinked" → "anonymous" migrieren

```sql
UPDATE calls
SET customer_link_status = 'anonymous'
WHERE customer_link_status = 'unlinked';
```

**Dann haben wir**:
| Status | Bedeutung |
|--------|-----------|
| **linked** | customer_id vorhanden |
| **name_only** | Nur customer_name |
| **anonymous** | Keine Daten (kombiniert anonymous + unlinked) |

**Vorteile**:
- ✅ Einfacher (3 statt 4 Kategorien)
- ✅ Klarer Unterschied
- ✅ Minimal-invasiv (nur Status umbenennen)

---

### Option 3: Noch klarere Namen (DE)

| Alter Status | Neuer Status | Badge-Text |
|--------------|--------------|------------|
| linked | **kunde** | "✓ Kunde" |
| name_only | **name** | "📝 Name" |
| anonymous | **unbekannt** | "❓ Unbekannt" |
| unlinked | **unbekannt** | "❓ Unbekannt" |

**Vorteile**:
- ✅ Deutsche Namen (konsistent mit UI)
- ✅ Selbsterklärend
- ✅ Keine technischen Begriffe

---

## 🎯 Empfehlung

### Sofort-Fix (5 Minuten)

**Merge "unlinked" → "anonymous"**:

```sql
-- Fix die 6 unlinked Calls
UPDATE calls
SET customer_link_status = 'anonymous'
WHERE customer_link_status = 'unlinked'
  AND customer_name IS NULL
  AND customer_id IS NULL;
```

**Display Update**:
```php
// In CallResource.php Datenqualität-Spalte
return match ($status) {
    'linked' => '✓ Verknüpft',
    'name_only' => '⚠ Nur Name',
    'anonymous' => '❓ Unbekannt',  // Vereinfacht!
    // 'unlinked' entfällt (alle sind jetzt 'anonymous')
};
```

**Resultat**: 3 klare Kategorien statt 4 verwirrende!

---

### Langfristig (Optional)

**Umbenennen zu klareren Namen**:
- linked → verified_customer
- name_only → name_known
- anonymous → no_data

**Oder auf Deutsch**:
- linked → kunde
- name_only → name
- anonymous → unbekannt

---

## 📋 Aktuelle Badge-Texte (VERWIRREND)

```
✓ Verknüpft     → OK ✅
⚠ Nur Name      → OK ✅
👤 Anonym       → ❓ (68 Calls)
○ Nicht verknüpft → ❓ (6 Calls) - WAS IST DER UNTERSCHIED?
```

**User denkt**: "Was ist der Unterschied zwischen Anonym und Nicht verknüpft?"

**Antwort**: Es gibt **keinen sinnvollen Unterschied** - beides bedeutet "keine Kundendaten"!

---

## ✅ Vereinfachter Vorschlag

### 3 Kategorien (Klar & Eindeutig)

#### 1. Kunde (Verifiziert)
```
Bedingung: customer_id IS NOT NULL
Badge: "✓ Kunde" (grün)
Tooltip: "Mit Kundenprofil verknüpft"
Beispiel: Call 599
```

#### 2. Name bekannt (Unverifiziert)
```
Bedingung: customer_name IS NOT NULL AND customer_id IS NULL
Badge: "📝 Name bekannt" (orange)
Tooltip: "Nur Name, kein Kundenprofil"
Beispiel: Call 611 (Schulze)
```

#### 3. Unbekannt (Keine Daten)
```
Bedingung: customer_name IS NULL AND customer_id IS NULL
Badge: "❓ Unbekannt" (grau)
Tooltip: "Keine Kundendaten"
Beispiel: Call 600, 607, 496-499, 514
```

**Klar**: Jede Kategorie hat eindeutige Bedeutung!

---

## 🎯 Entscheidung

Möchtest du:

**A) Sofort-Fix** (5 Min):
- Merge "unlinked" → "anonymous"
- Badge umbenennen: "👤 Anonym" → "❓ Unbekannt"
- 3 klare Kategorien

**B) Komplette Umbenennung** (30 Min):
- linked → kunde
- name_only → name
- anonymous → unbekannt
- Deutsches Schema

**C) Nur erklären**:
- Dokumentation was es bedeutet
- System lassen wie ist

---

**Was bevorzugst du?**
