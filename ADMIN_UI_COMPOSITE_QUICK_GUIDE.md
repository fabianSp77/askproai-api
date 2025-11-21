# 🎨 Admin UI - Composite Services Konfiguration (5 Minuten)
## Sofort-Lösung ohne Code-Änderungen

**Problem:** Dauerwelle wird als einfacher Termin gebucht
**Lösung:** Manuell in Filament Admin UI auf composite umstellen

**Zeit:** 5 Minuten
**Kein Deployment nötig:** Änderungen sofort aktiv!

---

## 📋 SCHRITT-FÜR-SCHRITT ANLEITUNG

### **1. Login zum Admin Panel**

```
URL: https://your-domain.de/admin
Login: Deine Admin-Credentials
```

---

### **2. Zu Dienstleistungen navigieren**

```
Menü → Dienstleistungen (Services)
oder
Menü → Services
```

---

### **3. Dauerwelle bearbeiten**

1. **Suche** "Dauerwelle" in der Liste
2. **Klicke** auf den Namen oder das Edit-Icon (Stift)
3. Du bist jetzt im Bearbeiten-Modus

---

### **4. Composite Service aktivieren**

Scrolle nach unten bis zur Sektion **"Composite Service"**

**Toggle aktivieren:**
```
[X] Komposite Dienstleistung aktivieren
```

**→ Neue Felder erscheinen!**

---

### **5. Segmente konfigurieren**

#### **Option A: Template verwenden (EMPFOHLEN - 2 Klicks)**

1. **Klicke** auf Dropdown "Service-Template verwenden"
2. **Wähle:** "🎨 Friseur Premium (2h 40min mit Pausen)"
3. **Fertig!** Segmente sind automatisch konfiguriert

#### **Option B: Manuell konfigurieren**

**Segment A:**
```
Key: A (automatisch)
Name: Vorbereitung & Auftrag
Duration: 20 Minuten
Gap After: 25 Minuten
✓ Prefer Same Staff
```

**Segment B:** (Klicke "+ Add Segment")
```
Key: B (automatisch)
Name: Ausspülen & Styling
Duration: 40 Minuten
Gap After: 0 Minuten
✓ Prefer Same Staff
```

---

### **6. Pause Policy setzen**

```
Gap/Pause Policy: "Staff unavailable during gaps" (blocked)
```

**Bedeutung:**
- `free`: Mitarbeiter kann während Pause andere Kunden bedienen
- `blocked`: Mitarbeiter ist während Pause reserviert
- `flexible`: Automatische Entscheidung basierend auf Verfügbarkeit

**Für Dauerwelle:** `blocked` (Mitarbeiter bleibt reserviert)

---

### **7. Speichern**

```
Klicke: "Save" (oben rechts)
```

**✅ FERTIG!** Dauerwelle ist jetzt ein Composite Service!

---

## 🧪 TESTEN

### **Test 1: Admin UI Vorschau**

Nach dem Speichern solltest du sehen:

```
Dauerwelle
├─ Composite: ✓ Yes
├─ Total Duration: 120 minutes
├─ Segments: 2
│  ├─ A: Vorbereitung & Auftrag (20 min) → Gap 25 min
│  └─ B: Ausspülen & Styling (40 min)
├─ Active Time: 60 minutes
└─ Gap Time: 25 minutes
```

---

### **Test 2: Booking Test (Web Interface)**

1. Gehe zu Buchungsseite (falls vorhanden)
2. Wähle "Dauerwelle"
3. Sollte zeigen:
   ```
   Dauerwelle (ca. 90 Min mit Pause)
   - Vorbereitung: 10:00-10:20
   - Pause: 10:20-10:45
   - Styling: 10:45-11:25
   ```

---

### **Test 3: Phone Call (nach Retell Integration)**

```
Anruf: "Ich möchte eine Dauerwelle für morgen um 10 Uhr"
Agent: "Dauerwelle dauert ca. 90 Minuten mit Pause. Passt das?"
Kunde: "Ja"

Erwartet: 2 Termine im Kalender
  - 10:00-10:20: Vorbereitung
  - 10:45-11:25: Styling
```

---

## 🎯 WIEDERHOLE FÜR ANDERE SERVICES

### **Färben Langhaar**

```
Composite: ✓ Yes
Segments:
  A: Farbauftrag (35 min) → Gap 35 min
  B: Ausspülen & Pflege (25 min)
Total: 95 min (shown as 120 for booking buffer)
```

---

### **Strähnchen Komplett**

```
Composite: ✓ Yes
Segments:
  A: Strähnchenauftrag (45 min) → Gap 35 min
  B: Ausspülen & Styling (35 min)
Total: 115 min (shown as 150 for booking buffer)
```

---

### **Keratin-Behandlung**

```
Composite: ✓ Yes
Segments:
  A: Vorbereitung (15 min) → Gap 5 min
  B: Keratin Auftrag (55 min) → Gap 60 min
  C: Ausspülen & Föhnen (35 min)
Total: 170 min (shown as 180 for booking buffer)
```

---

## ⚡ VORTEILE (SOFORT SICHTBAR)

✅ **Für Kunden:**
- Realistische Zeitplanung
- Klarheit über Ablauf

✅ **Für Mitarbeiter:**
- Pausen eingeplant
- Keine Überbuchung
- Zeit für andere Aufgaben während Einwirkzeit

✅ **Für Kalender:**
- Korrekte Zeitblöcke
- Übersichtliche Planung
- Vermeidung von Konflikten

---

## 🔍 TROUBLESHOOTING

### **Problem: Toggle "Composite" nicht sichtbar**

**Lösung:** Scrolle weiter nach unten, Sektion ist unter "Pricing & Availability"

---

### **Problem: Segments verschwinden beim Speichern**

**Prüfe:**
1. Mindestens 2 Segmente definiert?
2. Jedes Segment hat Name und Duration?
3. Keys sind unique (A, B, C)?

---

### **Problem: Buchung funktioniert nicht**

**Checke:**
1. Service hat `is_active = true`?
2. Cal.com Event Type IDs konfiguriert?
3. Mitarbeiter zugeordnet?

---

## 📞 NÄCHSTE SCHRITTE

Nach manueller Konfiguration:

1. **Kurz-Test:** Buche Dauerwelle über Web-Interface
2. **Daten prüfen:** Check Appointments Tabelle (`is_composite = true`?)
3. **Retell Integration:** Warte auf Code-Update für Phone Calls
4. **Live Test:** Phone Call Buchung testen

---

## 💡 TIPP: Template anpassen

Templates können angepasst werden in:
```
app/Filament/Resources/ServiceResource.php
Lines 158-167
```

Neue Templates hinzufügen:
```php
'perm_service' => '💆 Dauerwelle Service (90min mit Pause)',
```

---

**Zeit gespart:** 3 Stunden Code-Änderung
**Funktioniert ab:** SOFORT nach Speichern
**Risiko:** Minimal (nur Daten-Update, kein Code)

---

Viel Erfolg! 🚀
