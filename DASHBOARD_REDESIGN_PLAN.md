# Dashboard Redesign Plan für AskProAI

## Vision: Ein Dashboard das sofort zeigt was wichtig ist

### Neue Widget-Struktur (maximal 5 Kern-Widgets)

#### 1. **KPI Übersicht** (Ganz oben, volle Breite)
```
┌─────────────────────────────────────────────────────────────────┐
│ 📈 Umsatz Heute      📞 Anrufe      🎯 Konversion    👥 Neue Kunden │
│    2.450€            127            42%              8             │
│    ↑ +12%            ↑ +8%          ↑ +3%            ↑ +2          │
└─────────────────────────────────────────────────────────────────┘
```

#### 2. **Live Operations Board** (Links, 60% Breite)
- Echtzeit-Terminkalender
- Aktive Anrufe
- Nächste Termine
- Mitarbeiter-Status

#### 3. **Geschäftsentwicklung** (Rechts, 40% Breite)
- MRR Trend (Linienchart)
- Filial-Vergleich
- Top Services
- Auslastung

#### 4. **System & Compliance** (Unten links, 50%)
- API Status (Cal.com, Retell.ai)
- DSGVO Compliance ✓
- Kassenbuch Sync ✓
- Backups ✓

#### 5. **Schnellzugriff** (Unten rechts, 50%)
- ➕ Neuer Termin
- 📞 Anrufliste
- 👥 Kundenverwaltung
- ⚙️ Einstellungen

### Design-Prinzipien

1. **Komplett Deutsch**
   - Alle Labels, Titel, Beschreibungen auf Deutsch
   - Deutsche Datums-/Zeitformate (DD.MM.YYYY, HH:MM Uhr)
   - Deutsche Währungsformatierung (1.234,56 €)

2. **Visuelle Hierarchie**
   - KPIs ganz oben mit großen Zahlen
   - Farbcodierung: Grün = gut, Rot = Handlungsbedarf
   - Trend-Indikatoren (↑↓) bei allen Metriken

3. **Mobile First**
   - Responsive Grid (1 Spalte mobil, 2-3 tablet, 4 desktop)
   - Touch-optimierte Buttons (min. 44px)
   - Swipe-Gesten für Widget-Navigation

4. **Performance**
   - Nur 1 Query für alle KPIs
   - Lazy Loading für Charts
   - WebSocket für Echtzeit-Updates

### Implementierungs-Prioritäten

1. **Phase 1: Aufräumen** (1 Tag)
   - 90% der Widgets löschen
   - Dashboard.php neu strukturieren
   - Einheitliche deutsche Sprache

2. **Phase 2: Core Widgets** (2 Tage)
   - KPI Übersicht
   - Live Operations Board
   - Geschäftsentwicklung

3. **Phase 3: Polish** (1 Tag)
   - Visuelle Verbesserungen
   - Mobile Optimierung
   - Performance Tuning

### Erwartetes Ergebnis

- **Ladezeit**: < 1 Sekunde
- **Wichtigste KPIs**: Sofort sichtbar ohne Scrollen
- **Handlungsempfehlungen**: Klar erkennbar (rote Alerts)
- **Mobile Nutzung**: Voll funktionsfähig
- **Compliance**: DSGVO-konform mit sichtbarem Status