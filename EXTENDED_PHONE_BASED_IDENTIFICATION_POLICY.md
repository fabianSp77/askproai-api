# Erweiterte Telefon-basierte Identifikations-Policy

**Datum:** 2025-10-06
**Status:** 📋 PLANUNG
**Priority:** HIGH (Usability-Verbesserung)

---

## 🎯 Problem Statement

**Aktuell:**
- Exakte Namensübereinstimmung wird für ALLE Anrufer verlangt (auch mit Telefonnummer)
- Spracherkennungsfehler blockieren legitime Kunden, selbst wenn Telefonnummer übertragen wird
- Telefonnummer = starke Authentifizierung wird nicht voll ausgenutzt

**Beispiel Call 691:**
- Kunde ruft mit Telefonnummer an (nicht anonymous)
- Spracherkennung: "Hansi Sputa"
- Datenbank: "Hansi Sputer"
- ❌ Kunde wird blockiert, obwohl Telefonnummer korrekt identifiziert

**Neue Anforderung:**
> "Wenn die Telefonnummer übertragen wird und wir können die Telefonnummer mit der Filiale/dem Unternehmen zuordnen, dann sollte es möglich sein, auch ohne exakten Namen den Termin zu finden und zu bearbeiten."

---

## 📊 Neue Identifikations-Hierarchie

### Authentifizierungs-Stärke

| Strategy | Identifikator | Sicherheit | Name-Matching |
|----------|--------------|------------|---------------|
| **Strategy 1** | customer_id | ⭐⭐⭐⭐⭐ | Nicht relevant (bereits verknüpft) |
| **Strategy 2** | Telefonnummer | ⭐⭐⭐⭐⭐ | **Phonetisch/Fuzzy erlaubt** ✅ |
| **Strategy 3** | Name only (anonymous) | ⭐⭐ | **Exakt erforderlich** ⚠️ |
| **Strategy 4** | Call metadata | ⭐⭐ | **Exakt erforderlich** ⚠️ |

### Begründung

**Warum Telefonnummer = starke Auth:**
- ✅ Telefonnummer ist eindeutig (ein Kunde, eine Nummer)
- ✅ Telefonnummer wird vom Telefonnetz verifiziert (kein Spoofing bei regulären Calls)
- ✅ Kunde HAT sein Handy dabei (Besitz-basierte Authentifizierung)
- ✅ Name kann sich ändern (Heirat, Rechtschreibfehler in DB)
- ✅ Spracherkennung kann Namen falsch verstehen

**Warum Name allein = schwache Auth:**
- ⚠️ Mehrere Kunden können ähnliche Namen haben
- ⚠️ Spracherkennung fehlerhaft bei Namen
- ⚠️ Anonymous Caller = keine zusätzliche Verifikation
- ⚠️ Sicherheitsrisiko bei Namens-Fuzzy-Matching

---

## 🔧 Implementierungs-Plan

### Phase 1: Cancel Appointment (Lines 465-504)

**Änderung bei Strategy 2 (Phone Number Match):**

**VORHER:**
```php
// Strategy 2: Search by phone number (if not anonymous)
if (!$customer && $call->from_number && $call->from_number !== 'anonymous') {
    $normalizedPhone = preg_replace('/[^0-9+]/', '', $call->from_number);
    $customer = Customer::where('company_id', $call->company_id)
        ->where('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%')
        ->first();
    if ($customer) {
        Log::info('✅ Found customer via phone', ['customer_id' => $customer->id]);
    }
}

// Strategy 3: Exact name match (anonymous only)
if (!$customer && $customerName && $call->company_id) {
    // Exakte Übereinstimmung IMMER verlangt
    $customer = Customer::where('company_id', $call->company_id)
        ->where('name', $customerName)
        ->first();
}
```

**NACHHER:**
```php
// Strategy 2: Search by phone number (if not anonymous)
// ENHANCED: Phone = strong auth, name verification optional
if (!$customer && $call->from_number && $call->from_number !== 'anonymous') {
    $normalizedPhone = preg_replace('/[^0-9+]/', '', $call->from_number);

    // Try exact company match first
    $customer = Customer::where('company_id', $call->company_id)
        ->where(function($q) use ($normalizedPhone) {
            $q->where('phone', $normalizedPhone)
              ->orWhere('phone', 'LIKE', $normalizedPhone . '%');
        })
        ->first();

    // Fallback: Cross-tenant search (bereits vorhanden in reschedule)
    if (!$customer) {
        $customer = Customer::where(function($q) use ($normalizedPhone) {
            $q->where('phone', $normalizedPhone)
              ->orWhere('phone', 'LIKE', $normalizedPhone . '%');
        })->first();

        if ($customer && $customer->company_id !== $call->company_id) {
            Log::warning('⚠️ Cross-tenant customer via phone', [
                'customer_company' => $customer->company_id,
                'call_company' => $call->company_id
            ]);
        }
    }

    if ($customer) {
        Log::info('✅ Found customer via phone - STRONG AUTH', [
            'customer_id' => $customer->id,
            'auth_method' => 'phone_number',
            'security_level' => 'high',
            'name_matching' => 'not_required'
        ]);

        // Optional: Verify name similarity for logging (nicht blockierend!)
        if ($customerName && $customer->name !== $customerName) {
            $similarity = similar_text($customer->name, $customerName, $percent);
            Log::info('📊 Name mismatch detected (phone auth active)', [
                'db_name' => $customer->name,
                'spoken_name' => $customerName,
                'similarity' => round($percent, 2) . '%',
                'action' => 'proceeding_with_phone_auth'
            ]);
        }

        // Link customer to call
        $call->update(['customer_id' => $customer->id]);
    }
}

// Strategy 3: Search by customer_name (ONLY for anonymous callers)
// SECURITY: Require 100% exact match - no fuzzy matching without phone
if (!$customer && $customerName && $call->from_number === 'anonymous' && $call->company_id) {
    Log::info('📞 Anonymous caller - EXACT name match required', [
        'customer_name' => $customerName,
        'company_id' => $call->company_id,
        'security_policy' => 'exact_match_only',
        'reason' => 'no_phone_verification'
    ]);

    // Only exact match allowed - no LIKE queries for security
    $customer = Customer::where('company_id', $call->company_id)
        ->where('name', $customerName)
        ->first();

    if ($customer) {
        Log::info('✅ Found customer via EXACT name match', [
            'customer_id' => $customer->id,
            'auth_method' => 'name_only',
            'security_level' => 'low',
            'match_type' => 'exact'
        ]);
        $call->update(['customer_id' => $customer->id]);
    } else {
        Log::warning('❌ No customer found - exact name match required', [
            'search_name' => $customerName,
            'reason' => 'Anonymous caller requires exact name match for security'
        ]);
    }
}
```

### Phase 2: Reschedule Appointment (Lines 810-881)

**Identische Änderungen:**
- Strategy 2: Phone-based identification = strong auth, name optional
- Strategy 3: Name-only (anonymous) = exact match erforderlich

### Phase 3: Optional - Phonetisches Matching für Phone-Auth

**Enhancement (optional):**
Wenn Telefonnummer vorhanden UND Name nicht exakt matched, phonetisches Matching versuchen:

```php
// After phone-based customer found
if ($customer && $customerName && $customer->name !== $customerName) {
    // Phone auth is already strong - use phonetics for better UX
    if (soundex($customer->name) === soundex($customerName)) {
        Log::info('🔊 Phonetic name match (phone-verified)', [
            'db_name' => $customer->name,
            'spoken_name' => $customerName,
            'phonetic_match' => true,
            'auth_level' => 'phone_verified'
        ]);
    } else {
        Log::warning('⚠️ Name mismatch despite phone match', [
            'db_name' => $customer->name,
            'spoken_name' => $customerName,
            'phonetic_match' => false,
            'proceeding' => true,
            'reason' => 'phone_auth_sufficient'
        ]);
    }
}
```

---

## 📋 Test-Szenarien

### Szenario 1: Phone-Match mit Name-Mismatch (SOLLTE FUNKTIONIEREN)
```yaml
Setup:
  - DB Customer: name="Hansi Sputer", phone="+493012345678", company_id=15
  - Anrufer: phone="+493012345678", sagt "Hansi Sputa"

Ablauf:
  1. Strategy 1: customer_id → NULL
  2. Strategy 2: phone="+493012345678" → ✅ Customer 342 gefunden
  3. Name check: "Sputa" ≠ "Sputer" → ⚠️ Logging, aber KEIN Block
  4. Result: ✅ Customer identifiziert, Termin kann bearbeitet werden

Erwartet: ✅ SUCCESS - Phone auth ist ausreichend
```

### Szenario 2: Anonymous mit exaktem Namen (SOLLTE FUNKTIONIEREN)
```yaml
Setup:
  - DB Customer: name="Hansi Sputer", phone="anonymous_xxx", company_id=15
  - Anrufer: phone="anonymous", sagt "Hansi Sputer" (EXAKT)

Ablauf:
  1. Strategy 1: customer_id → NULL
  2. Strategy 2: phone="anonymous" → Skip
  3. Strategy 3: name="Hansi Sputer" (exact) → ✅ Customer 342 gefunden
  4. Result: ✅ Customer identifiziert

Erwartet: ✅ SUCCESS - Exakter Name-Match
```

### Szenario 3: Anonymous mit Name-Mismatch (SOLLTE BLOCKIEREN)
```yaml
Setup:
  - DB Customer: name="Hansi Sputer", phone="anonymous_xxx", company_id=15
  - Anrufer: phone="anonymous", sagt "Hansi Sputa" (fehlt "r")

Ablauf:
  1. Strategy 1: customer_id → NULL
  2. Strategy 2: phone="anonymous" → Skip
  3. Strategy 3: name="Hansi Sputa" (exact) → ❌ Kein Match
  4. Result: ❌ Customer NICHT identifiziert

Erwartet: ❌ BLOCKED - Sicherheits-Policy greift
Fehlermeldung: "Entschuldigung, ich kann Ihren Termin ohne Rufnummernanzeige..."
```

### Szenario 4: Phone-Match, völlig anderer Name (Edge Case)
```yaml
Setup:
  - DB Customer: name="Hansi Sputer", phone="+493012345678", company_id=15
  - Anrufer: phone="+493012345678", sagt "Max Mustermann"

Ablauf:
  1. Strategy 2: phone="+493012345678" → ✅ Customer 342 gefunden
  2. Name check: "Max Mustermann" ≠ "Hansi Sputer" → ⚠️ HIGH ALERT Logging
  3. Result: ✅ Customer identifiziert (phone auth)

Erwartet: ✅ SUCCESS mit WARNING-Log
Begründung: Telefonnummer = stärkste Auth, Name könnte geändert sein
```

### Szenario 5: Cross-Tenant Phone Match
```yaml
Setup:
  - DB Customer: name="Hansi Sputer", phone="+493012345678", company_id=1
  - Anrufer: phone="+493012345678", company_id=15 (andere Filiale!)

Ablauf:
  1. Strategy 2: phone → Company 15 search → NULL
  2. Fallback: Cross-tenant search → ✅ Customer gefunden (company_id=1)
  3. Cross-tenant warning logged
  4. Result: ✅ Customer identifiziert (bereits implementiert)

Erwartet: ✅ SUCCESS mit Cross-Tenant Warning
```

---

## 🔒 Sicherheits-Analyse

### Neue Policy vs Alte Policy

**ALTE POLICY (vor dieser Änderung):**
```
Phone Match → Exakter Name IMMER erforderlich
Anonymous → Exakter Name erforderlich
```
**Problem:** Spracherkennungsfehler blockieren auch Telefon-verifizierte Kunden

**NEUE POLICY:**
```
Phone Match → Name-Matching NICHT erforderlich (phone = strong auth)
Anonymous → Exakter Name erforderlich (name = weak auth)
```
**Vorteil:** Nutzt Telefon als stärkeren Identifikator

### Risiko-Bewertung

**Risiko 1: Telefon-Spoofing**
- **Wahrscheinlichkeit:** Sehr gering (reguläre Telefonie)
- **Auswirkung:** Fremder könnte Termin ändern
- **Mitigation:**
  - Cross-tenant checks bereits vorhanden
  - Extensive Logging aller Identifikationen
  - Name-Mismatch-Warnings für Monitoring

**Risiko 2: Falsche Telefonnummer in DB**
- **Wahrscheinlichkeit:** Mittel
- **Auswirkung:** Kunde A ruft an, wird als Kunde B identifiziert
- **Mitigation:**
  - Name-Similarity-Logging (Monitoring)
  - Bei großer Diskrepanz: Alert für Admin

**Risiko 3: SIM-Karten-Diebstahl**
- **Wahrscheinlichkeit:** Sehr gering
- **Auswirkung:** Dieb könnte Termine ändern
- **Mitigation:**
  - Gleiches Risiko wie bei Banking-Apps
  - Standard-Sicherheitsmodell: Besitz = Authentifizierung

### DSGVO-Konformität

✅ **Artikel 32 - Sicherheit der Verarbeitung:**
- Telefonnummer = technische Maßnahme zur Authentifizierung
- Logging = Nachweis der Verarbeitungstätigkeit
- Nachvollziehbarkeit = Audit Trail

✅ **Artikel 5 - Grundsätze:**
- Zweckbindung: Telefon für Identifikation genutzt
- Datenminimierung: Nur notwendige Daten (phone, name)
- Integrität: Schutz vor unbefugtem Zugriff

---

## 📊 Erwartete Auswirkungen

### Usability-Verbesserung
```
Vorher (mit Call 691 als Beispiel):
- Phone-Match: 1/1 Versuche
- Name-Match: 0/2 Versuche (Sprachfehler)
- Success-Rate: 0% ❌

Nachher (mit neuer Policy):
- Phone-Match: 1/1 Versuche ✅
- Name-Match: Nicht erforderlich
- Success-Rate: 100% ✅
```

### Metriken

**KPIs zu überwachen:**
```sql
-- Success Rate nach Auth-Methode
SELECT
    JSON_EXTRACT(metadata, '$.auth_method') as auth_method,
    COUNT(*) as total,
    SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful,
    ROUND(AVG(CASE WHEN success THEN 100 ELSE 0 END), 2) as success_rate
FROM appointments
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY auth_method;

-- Name-Mismatch-Rate bei Phone-Auth
SELECT
    DATE(created_at) as date,
    COUNT(*) as phone_auths,
    SUM(CASE WHEN JSON_EXTRACT(metadata, '$.name_mismatch') = true THEN 1 ELSE 0 END) as name_mismatches,
    ROUND(AVG(JSON_EXTRACT(metadata, '$.name_similarity')), 2) as avg_similarity
FROM appointments
WHERE JSON_EXTRACT(metadata, '$.auth_method') = 'phone_number'
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

---

## 🎯 Empfehlung

### ⭐ IMPLEMENTIEREN

**Begründung:**
1. ✅ Telefonnummer ist objektiv stärkerer Identifikator als Name
2. ✅ Löst das Spracherkennungs-Problem pragmatisch
3. ✅ Sicherheitsrisiko vertretbar (Standard bei Banking/Auth-Apps)
4. ✅ Verbessert Kundenerfahrung erheblich
5. ✅ DSGVO-konform

**Reihenfolge:**
1. Implementierung in `cancel_appointment` (Lines 465-504)
2. Implementierung in `reschedule_appointment` (Lines 810-881)
3. Testing mit allen 5 Szenarien
4. Monitoring-Dashboard für Name-Mismatch-Rate
5. Optional: Phonetisches Matching als Enhancement

---

**Status:** 📋 Bereit zur Implementierung
**Nächster Schritt:** User-Freigabe einholen
**Geschätzte Implementierungszeit:** 30 Minuten
