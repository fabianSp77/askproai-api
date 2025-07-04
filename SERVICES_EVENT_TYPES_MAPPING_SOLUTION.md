# Services & Event Types Mapping Solution

## Issue #204 - Dienstleistungen und Event Types Verknüpfung

### Problem
Der User war unsicher, ob es Sinn macht, interne "Dienstleistungen" zu haben, die mit Cal.com Event Types verknüpft werden, oder ob man direkt Event Types verwenden sollte.

### Analyse
Nach gründlicher Analyse der Architektur wurde festgestellt:

1. **Dual System existiert bereits**:
   - **Services** = Interne Geschäftslogik (Preise, Skills, KI-Beschreibungen)
   - **Event Types** = Cal.com Kalenderlogik (Verfügbarkeit, Buchungseinstellungen)
   - Eine Mapping-Tabelle existiert, wird aber nicht genutzt

2. **Vorteile des Dual Systems**:
   - Services enthalten geschäftskritische Daten, die Cal.com nicht verwaltet
   - Die KI versteht einfache Service-Namen besser
   - Zukunftssicherheit bei Anbieterwechsel
   - Ein Service kann mehrere Event Types haben (z.B. 30min, 60min Varianten)

### Implementierte Lösung

#### 1. Klare Konzept-Erklärung
```html
<div class="info-box">
  • Cal.com Event Types = Kalender-Einstellungen (Dauer, Verfügbarkeit)
  • Dienstleistungen = Ihre Geschäftslogik (Preise, Skills, KI-Beschreibung)
  • Jede Dienstleistung wird mit einem Event Type verknüpft
</div>
```

#### 2. Import-Funktion
- Button "Cal.com Event Types importieren"
- Erstellt automatisch Services basierend auf Event Types
- Intelligente Kategorisierung (Haare, Beauty, Medical, etc.)
- Verhindert Duplikate

#### 3. Service-Verwaltung
Jeder Service hat nun:
- **Name**: Kundenfreundlicher Name für KI
- **Event Type**: Verknüpfung zu Cal.com
- **Preis**: Geschäftsspezifisch
- **Dauer**: Kann Event Type überschreiben
- **Kategorie**: Für bessere Organisation
- **KI-Beschreibung**: Hilft der KI bei der Beratung
- **Online buchbar**: Kontrolle über Telefon-Buchbarkeit

#### 4. Mitarbeiter-Zuordnung
- Mitarbeiter können Services anbieten
- Dynamische Service-Auswahl basierend auf definierten Services

### Code-Änderungen

1. **getServicesAndStaffFields()**: Komplett neue Implementierung
2. **importAndMapEventTypes()**: Neue Methode für Event Type Import
3. **updateServices()**: Speichert Service-Daten mit Event Type Verknüpfung
4. **updateStaff()**: Verwaltet Mitarbeiter-Service-Zuordnungen
5. **loadServices()/loadStaff()**: Erweitert für alle neuen Felder

### Workflow für Nutzer

1. **Cal.com Event Types importieren** (optional)
2. **Services definieren** mit geschäftsspezifischen Details
3. **Event Types zuordnen** für Kalenderbuchungen
4. **Mitarbeiter zuweisen** zu Services

### Vorteile der Lösung

✅ **Flexibilität**: Services und Event Types getrennt aber verknüpft
✅ **Geschäftslogik**: Preise, Skills, etc. bleiben intern
✅ **KI-Optimiert**: Einfache Namen für Spracherkennung
✅ **Zukunftssicher**: Unabhängig von Cal.com
✅ **Benutzerfreundlich**: Klare Konzepterklärung

### Nächste Schritte

1. Template-System für Branchen implementieren
2. Bulk-Import für Services
3. Automatische Synchronisation mit Cal.com
4. Multi-Event-Type pro Service Support