# 📊 KPI Dashboard Template - Business Success Metriken

> **Ziel**: ROI beweisen, Erfolg messen, Wachstum skalieren!

## 🎯 Quick KPI Check
```bash
# Ein-Befehl Business Health Check
php artisan kpi:dashboard --company-id=X --format=pretty
```

---

## 💰 ROI CALCULATOR

### Eingabe-Parameter:
```yaml
Monatliche Kosten:
- AskProAI Lizenz: 299€
- Telefonnummer: 29€
- Cal.com (optional): 15€
GESAMT: 343€/Monat

Bisherige Kosten (manuell):
- Rezeptionist (Teilzeit): 1.200€/Monat
- Verpasste Anrufe: ~500€ Umsatzverlust
- Überstunden: 300€
GESAMT ALT: 2.000€/Monat
```

### 🧮 ROI Berechnung:
```javascript
// ROI Formula
const monthlyKosten = 343;
const eingesparteKosten = 2000;
const zusätzlicherUmsatz = anrufe * conversionRate * durchschnittlicherTerminwert;

const ROI = ((eingesparteKosten + zusätzlicherUmsatz - monthlyKosten) / monthlyKosten) * 100;

// Beispiel:
// Ersparnis: 2000€ - 343€ = 1.657€
// Zusatz-Umsatz: 50 Termine × 80€ = 4.000€
// ROI = (1657 + 4000) / 343 × 100 = 1.649% 🚀
```

---

## 📈 KERN-METRIKEN DASHBOARD

### 1️⃣ **Anruf-Performance**
```bash
# Live-Metriken abrufen
php artisan kpi:calls --period=today

📞 ANRUF-STATISTIKEN (Heute)
├── Eingehende Anrufe: 127
├── Angenommen von AI: 124 (97.6%)
├── Durchschnittliche Dauer: 2:34 Min
├── Erfolgsrate: 89.5%
└── Verpasste Anrufe: 3 (2.4%) ⚠️
```

**Zielwerte:**
- Annahmerate: > 95% ✅
- Erfolgsrate: > 85% ✅
- Ø Dauer: < 3 Min ✅
- Verpasst: < 5% ✅

### 2️⃣ **Termin-Conversion**
```bash
# Conversion Funnel
php artisan kpi:conversion --period=week

🎯 CONVERSION FUNNEL (Diese Woche)
Anrufe: 850 ━━━━━━━━━━━━━━━━━━━━ 100%
   ↓
Qualifiziert: 680 ━━━━━━━━━━━━━━ 80%
   ↓
Termin angeboten: 544 ━━━━━━━━━ 64%
   ↓
Termin gebucht: 435 ━━━━━━━ 51.2% 🎯
   ↓
Erschienen: 370 ━━━━━ 43.5%
   ↓
Umsatz: 29.600€ 💰
```

**Branchen-Benchmarks:**
- Medizin: 45-55% Booking Rate ✅
- Beauty: 55-65% Booking Rate
- Handwerk: 35-45% Booking Rate

### 3️⃣ **Kunden-Zufriedenheit**
```bash
# NPS & Feedback
php artisan kpi:satisfaction

😊 KUNDENZUFRIEDENHEIT
├── AI-Freundlichkeit: 4.6/5 ⭐
├── Verständlichkeit: 4.3/5 ⭐
├── Terminfindung: 4.7/5 ⭐
├── Gesamtzufriedenheit: 4.5/5 ⭐
└── NPS Score: +67 (Exzellent!)

Top-Feedback:
✅ "24/7 erreichbar ist super!"
✅ "Viel freundlicher als vorher"
⚠️ "Manchmal Dialekt-Probleme"
```

### 4️⃣ **Umsatz-Impact**
```bash
# Revenue Attribution
php artisan kpi:revenue --period=month

💰 UMSATZ-ANALYSE (Dieser Monat)
├── Termine via AI: 1.247
├── Ø Terminwert: 78€
├── Gesamt-Umsatz: 97.266€
├── Davon Neukunden: 31.450€ (32%)
└── Steigerung vs. Vorjahr: +156% 📈

NACH QUELLE:
├── Direkt-Anrufe: 72.340€ (74%)
├── Rückruf-Service: 18.200€ (19%)
└── Notdienst: 6.726€ (7%)
```

### 5️⃣ **Effizienz-Metriken**
```bash
# Operational Excellence
php artisan kpi:efficiency

⚡ EFFIZIENZ-METRIKEN
├── Ø Zeit bis Terminbuchung: 2:12 Min
├── First-Call-Resolution: 91%
├── Manuelle Eingriffe: 3.2%
├── System-Uptime: 99.97%
└── Cost per Booking: 0.38€

ZEITERSPARNIS:
├── Früher (manuell): 5-7 Min/Anruf
├── Jetzt (AI): 2:12 Min/Anruf
└── Ersparnis: 67% schneller! ⚡
```

---

## 📊 LIVE DASHBOARD HTML TEMPLATE

```html
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI KPI Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .kpi-card {
            background: #f0f9ff;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 48px;
            font-weight: bold;
            color: #3b82f6;
        }
        .metric-label {
            color: #64748b;
            font-size: 14px;
        }
        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>🎯 AskProAI Business Dashboard</h1>
        
        <!-- ROI Card -->
        <div class="kpi-card">
            <div class="metric-label">Return on Investment</div>
            <div class="metric-value">1.649%</div>
            <div class="trend-up">↑ +234% vs. letzter Monat</div>
        </div>

        <!-- Conversion Rate -->
        <div class="kpi-card">
            <div class="metric-label">Anruf → Termin Rate</div>
            <div class="metric-value">51.2%</div>
            <div class="trend-up">↑ +5.3% diese Woche</div>
        </div>

        <!-- Revenue Chart -->
        <canvas id="revenueChart"></canvas>
    </div>

    <script>
    // Auto-Update alle 30 Sekunden
    setInterval(() => {
        fetch('/api/kpi/live')
            .then(res => res.json())
            .then(data => updateDashboard(data));
    }, 30000);
    </script>
</body>
</html>
```

---

## 🎯 BRANCHEN-SPEZIFISCHE KPIs

### 🏥 **Medizin-Praxen**
```yaml
Kritische KPIs:
- No-Show Rate: < 10% (Ziel)
- Ø Wartezeit Neupatienten: < 7 Tage
- Privatpatienten-Quote: > 20%
- Recall-Success: > 60%
```

### 💇 **Beauty & Wellness**
```yaml
Kritische KPIs:
- Wiederkehrende Kunden: > 70%
- Ø Buchungswert: > 65€
- Zusatzservice-Quote: > 30%
- Social Media Mentions: > 10/Monat
```

### 🔧 **Handwerk & Service**
```yaml
Kritische KPIs:
- Notdienst-Response: < 30 Min
- Auftrags-Konversion: > 40%
- Ø Auftragswert: > 350€
- Kundenbewertung: > 4.5/5
```

---

## 📱 AUTOMATISIERTE REPORTS

### Täglicher Report (Email)
```bash
# Cron: 0 8 * * *
php artisan kpi:daily-report --email=geschaeftsfuehrer@firma.de
```

### Wöchentliches Management Summary
```bash
# Cron: 0 9 * * 1
php artisan kpi:weekly-summary --format=pdf --email=management@firma.de
```

### Monatlicher ROI Report
```bash
# Cron: 0 10 1 * *
php artisan kpi:monthly-roi --include-projections --email=cfo@firma.de
```

---

## 🚀 WACHSTUMS-TRACKING

### Growth Metrics
```bash
php artisan kpi:growth --period=quarter

📈 WACHSTUMS-METRIKEN Q4
├── Neue Kunden: +347 (+89%)
├── Umsatzwachstum: +156%
├── Marktanteil: 12% → 19%
├── Kundenbindung: 84%
└── Expansion Revenue: +34.200€

PROGNOSE Q1:
├── Erwartete Kunden: +520
├── Umsatzprognose: +187%
└── Break-Even: Bereits erreicht! ✅
```

---

## 🏆 SUCCESS STORIES GENERATOR

```bash
# Automatische Success Story
php artisan kpi:success-story --company-id=X

📖 SUCCESS STORY: Zahnarztpraxis Dr. Schmidt
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Vorher:
- 30% verpasste Anrufe
- 2h täglich Terminverwaltung
- Unzufriedene Patienten

Mit AskProAI:
✅ 97% Anrufe angenommen
✅ 0 Min Terminverwaltung
✅ 4.8/5 Patientenzufriedenheit
✅ 45.000€ Zusatzumsatz/Jahr

"Beste Investition ever!" - Dr. Schmidt
```

---

## 🎮 GAMIFICATION & GOALS

### Monatliche Challenges
```bash
php artisan kpi:challenges --current

🏆 AKTUELLE CHALLENGES
├── "Speed Demon": Ø Buchungszeit < 2 Min ⏱️
│   Status: 2:12 Min (Fast geschafft!)
├── "Conversion King": > 55% Booking Rate 👑
│   Status: 51.2% (Noch 3.8%!)
└── "Happy Customers": NPS > 70 😊
    Status: 67 (Fast da!)

REWARDS:
🥇 Alle Challenges = Feature-Upgrade gratis!
```

---

## 💡 OPTIMIERUNGS-VORSCHLÄGE

```bash
# AI-basierte Vorschläge
php artisan kpi:optimize-suggestions

💡 OPTIMIERUNGS-POTENZIALE
1. Prompt anpassen: +5% Conversion möglich
   → "Biete aktiv Alternativtermine an"
   
2. Anrufzeiten: Peaks um 11-12 Uhr
   → Zusätzliche Leitung empfohlen
   
3. No-Shows reduzieren: SMS-Reminder
   → Aktivieren für -8% No-Show Rate
   
4. Upsell-Chance: Zusatzservices
   → Bei Terminen > 30 Min anbieten

Geschätzter Impact: +12.000€/Monat 💰
```

> 📊 **Dashboard-URL**: https://app.askproai.de/kpi/{company-id}