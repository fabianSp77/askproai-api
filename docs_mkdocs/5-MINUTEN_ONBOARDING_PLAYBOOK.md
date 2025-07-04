# 🚀 5-Minuten Onboarding Playbook für AskProAI

> **Ziel**: Neuer Kunde ist in 5 Minuten live und empfängt Termine!

## ⏱️ Zeitplan
- **Minute 1-2**: Branche wählen & Account anlegen
- **Minute 3-4**: AI-Agent konfigurieren & testen
- **Minute 5**: Live schalten & ersten Testanruf

## 📊 Onboarding-Prozess Übersicht

```mermaid
flowchart LR
    Start([Neuer Kunde]) --> Branch{Branche wählen}
    Branch -->|Medizin| MedTemplate[Medizin-Template<br/>30 Min Slots<br/>DSGVO-konform]
    Branch -->|Beauty| BeautyTemplate[Beauty-Template<br/>60 Min Slots<br/>Preisliste]
    Branch -->|Handwerk| HandwerkTemplate[Handwerk-Template<br/>2h Slots<br/>Notdienst]
    
    MedTemplate --> Setup[Quick Setup<br/>90 Sek]
    BeautyTemplate --> Setup
    HandwerkTemplate --> Setup
    
    Setup --> AI[AI-Agent<br/>konfigurieren]
    AI --> Test[Test-Anruf<br/>durchführen]
    Test --> Live[Live schalten]
    Live --> Success([✅ Fertig!<br/>5 Min total])
    
    style Start fill:#e1f5e1
    style Success fill:#4caf50,color:#fff
    style Branch fill:#fff3cd
    style Setup fill:#cfe2ff
    style AI fill:#cfe2ff
    style Test fill:#cfe2ff
    style Live fill:#cfe2ff
```

---

## 📋 SCHRITT 1: Branche wählen (30 Sek)

### Branchen-Entscheidungsbaum

```mermaid
flowchart TD
    Q1{"Terminbasiertes<br/>Geschäft?"}
    Q1 -->|Ja| Q2{"Medizinischer<br/>Bereich?"}
    Q1 -->|Nein| NotSupported[Noch nicht<br/>unterstützt]
    
    Q2 -->|Ja| Medical[🏥 Medizin-Template]
    Q2 -->|Nein| Q3{"Beauty/<br/>Wellness?"}
    
    Q3 -->|Ja| Beauty[💇 Beauty-Template]
    Q3 -->|Nein| Q4{"Handwerk/<br/>Service?"}
    
    Q4 -->|Ja| Handwerk[🔧 Handwerk-Template]
    Q4 -->|Nein| Custom[Individuell<br/>konfigurieren]
    
    Medical --> Features1[✓ DSGVO-konform<br/>✓ Rezept-Hinweise<br/>✓ 24h Vorlauf]
    Beauty --> Features2[✓ Preisliste<br/>✓ Instagram<br/>✓ 2h Vorlauf]
    Handwerk --> Features3[✓ Notdienst<br/>✓ WhatsApp<br/>✓ Anfahrt]
    
    style Q1 fill:#fff3cd
    style Q2 fill:#fff3cd
    style Q3 fill:#fff3cd
    style Q4 fill:#fff3cd
    style Medical fill:#e3f2fd
    style Beauty fill:#fce4ec
    style Handwerk fill:#e8f5e9
    style NotSupported fill:#ffebee
```

### 🏥 **Medizin** (Arzt, Zahnarzt, Physiotherapie)
```bash
./setup-customer.sh --template="medical" --company="$FIRMENNAME"
```
**Voreinstellungen**:
- Terminlänge: 30 Min
- Vorlaufzeit: 24 Stunden
- DSGVO-konform
- Rezept-Hinweise aktiviert

### 💇 **Beauty** (Friseur, Kosmetik, Nails)
```bash
./setup-customer.sh --template="beauty" --company="$FIRMENNAME"
```
**Voreinstellungen**:
- Terminlänge: 60 Min
- Vorlaufzeit: 2 Stunden
- Preisliste integriert
- Instagram-Verlinkung

### 🔧 **Handwerk** (Installateur, Elektriker, KFZ)
```bash
./setup-customer.sh --template="craftsman" --company="$FIRMENNAME"
```
**Voreinstellungen**:
- Terminlänge: 2 Stunden
- Notdienst-Routing
- Anfahrtskosten-Hinweis
- WhatsApp-Integration

---

## 📋 SCHRITT 2: Basis-Setup (90 Sek)

### Quick Setup Command:
```bash
# Alles in einem Befehl!
php artisan askpro:quick-setup \
  --company="Zahnarztpraxis Dr. Schmidt" \
  --phone="+49 30 12345678" \
  --email="info@dr-schmidt.de" \
  --branch="Hauptpraxis Berlin"
```

### ✅ Was passiert automatisch:
- [x] Company wird angelegt
- [x] Branch wird erstellt  
- [x] Telefonnummer wird verknüpft
- [x] Retell Agent wird provisioniert
- [x] Cal.com Event Type wird erstellt
- [x] Email-Templates werden geladen

---

## 📋 SCHRITT 3: AI-Agent Prompt (60 Sek)

### 🏥 **Medizin Template**:
```
Du bist die freundliche Empfangskraft der {{company_name}}.

WICHTIG:
- Begrüße mit: "Praxis {{company_name}}, guten Tag!"
- Frage nach Versichertenstatus (Gesetzlich/Privat)
- Biete nur Termine ab morgen an
- Weise auf Terminabsage 24h vorher hin
- Beende mit: "Vielen Dank und auf Wiederhören!"

SERVICES:
- Erstberatung (30 Min)
- Kontrolltermin (15 Min)
- Behandlung (45 Min)
```

### 💇 **Beauty Template**:
```
Du bist die Rezeption von {{company_name}}.

BEGRÜSSUNG: "{{company_name}}, schönen guten Tag!"

WICHTIG:
- Frage nach gewünschter Behandlung
- Erwähne Preise bei Nachfrage
- Biete Zusatzservices an (z.B. Maniküre)
- Instagram: @{{instagram_handle}}

SERVICES:
- Haarschnitt Damen (45 Min) - 45€
- Haarschnitt Herren (30 Min) - 25€
- Farbe & Schnitt (120 Min) - 120€
```

### 🛠️ **Copy-Paste in Retell Dashboard**:
1. Gehe zu: https://dashboard.retellai.com/agents
2. Klicke auf deinen Agent
3. Füge Prompt ein → Save

---

## 📋 SCHRITT 4: Test-Anruf (60 Sek)

### 📞 **Test-Skript für ersten Anruf**:
```
1. Wähle: {{phone_number}}
2. Sage: "Ich möchte einen Termin vereinbaren"
3. Antworte auf Fragen (Service, Datum, Zeit)
4. Bestätige mit "Ja, passt perfekt"
5. Lege auf
```

### ✅ **Erfolgs-Checkliste**:
- [ ] AI hat freundlich begrüßt
- [ ] Verfügbare Termine wurden angeboten
- [ ] Kundendaten wurden korrekt erfasst
- [ ] Termin erscheint im Admin-Panel
- [ ] Bestätigungs-Email wurde versendet

### 🚨 **Fehler? Quick-Fixes**:

```mermaid
flowchart TD
    Problem{"Welches<br/>Problem?"}
    Problem -->|AI antwortet nicht| AI["AI-Problem"]
    Problem -->|Keine Termine| Cal["Calendar-Problem"]
    Problem -->|Email fehlt| Email["Email-Problem"]
    Problem -->|Anderes| Other["Siehe ERROR_PATTERNS.md"]
    
    AI --> AI1["php artisan horizon"]
    AI1 --> AI2["php artisan retell:test-connection"]
    AI2 --> AI3{"Gelöst?"}
    AI3 -->|Nein| Support1["WhatsApp Support:<br/>+49 176 12345678"]
    AI3 -->|Ja| Fixed1["✅ Behoben"]
    
    Cal --> Cal1["php artisan calcom:sync-availability"]
    Cal1 --> Cal2{"Gelöst?"}
    Cal2 -->|Nein| Support2["Support kontaktieren"]
    Cal2 -->|Ja| Fixed2["✅ Behoben"]
    
    Email --> Email1["php artisan queue:work --queue=emails"]
    Email1 --> Email2{"Gelöst?"}
    Email2 -->|Nein| Support3["SMTP prüfen"]
    Email2 -->|Ja| Fixed3["✅ Behoben"]
    
    style Problem fill:#fff3cd
    style AI fill:#ffebee
    style Cal fill:#ffebee
    style Email fill:#ffebee
    style Fixed1 fill:#c8e6c9
    style Fixed2 fill:#c8e6c9
    style Fixed3 fill:#c8e6c9
    style Support1 fill:#ff9800,color:#fff
    style Support2 fill:#ff9800,color:#fff
    style Support3 fill:#ff9800,color:#fff
```

```bash
# AI antwortet nicht?
php artisan horizon
php artisan retell:test-connection

# Keine Termine verfügbar?
php artisan calcom:sync-availability

# Email kommt nicht?
php artisan queue:work --queue=emails
```

---

## 🎯 SCHRITT 5: Live schalten (30 Sek)

### ✅ **Final Checklist**:
```bash
# Automatischer Go-Live Check
php artisan askpro:preflight-check

# Ausgabe sollte sein:
✅ Retell Agent: Active
✅ Phone Number: Connected  
✅ Cal.com: Synced
✅ Email: Configured
✅ Webhooks: Verified

🚀 READY FOR PRODUCTION!
```

### 📱 **Kunde informieren**:
```
Lieber Kunde,

Ihre AI-Telefonassistentin ist jetzt aktiv! 🎉

✅ Telefonnummer: {{phone_number}}
✅ Verfügbar: 24/7
✅ Admin-Panel: https://app.askproai.de

Erste Schritte:
1. Machen Sie einen Testanruf
2. Prüfen Sie den Termin im Admin-Panel
3. Passen Sie bei Bedarf den AI-Text an

Bei Fragen: support@askproai.de oder 0800-ASKPRO-AI

Viel Erfolg!
Ihr AskProAI Team
```

---

## 🆘 Notfall-Support

**Problem nicht gelöst?**
1. WhatsApp Hotline: +49 176 12345678
2. Email: urgent@askproai.de
3. Remote-Support: https://askproai.de/screenshare

**Häufigste Probleme**:
- "Webhook failed" → Siehe ERROR_PATTERNS.md Code WEBHOOK_001
- "Keine Anrufe" → Siehe ERROR_PATTERNS.md Code RETELL_001
- "DB Error" → Siehe ERROR_PATTERNS.md Code DB_001

---

## 🎓 Weiterführende Ressourcen

Nach erfolgreichem Onboarding:
- **Erweiterte Konfiguration**: ADVANCED_CONFIG.md
- **Multi-Branch Setup**: MULTI_LOCATION_GUIDE.md
- **Custom Prompts**: AI_PROMPT_ENGINEERING.md
- **Analytics Setup**: KPI_DASHBOARD_TEMPLATE.md

> 💡 **Pro-Tipp**: Speichern Sie dieses Playbook als PDF für Ihre Kunden!