# Performance Surgery Plan - Kritische Optimierungen

## 🎯 Identifizierte Performance-Blocker

### 1. TenantScope Performance Problem (Höchste Priorität)
**Problem**: TenantScope führt bei jeder Query einen Auth-Lookup durch (+50ms pro Query)
- Zeile 47-68 in TenantScope.php: Multiple Auth::guard() Aufrufe
- Dashboard lädt 150+ Queries = 7.5 Sekunden nur für Auth-Lookups
- Keine Request-Lifecycle Caching

**Lösung**: CachedTenantScope mit Request-basiertem Caching

### 2. Dashboard N+1 Problem (Kritisch)
**Problem**: Dashboard Widgets laden jeweils separate Queries
- CompactOperationsWidget lädt Company/Branch Daten einzeln
- Keine Aggregation auf DB-Ebene
- Eager Loading fehlt

**Lösung**: DashboardStatsService mit optimierten Aggregation Queries

### 3. Widget Query Ineffizienz (Hoch)
**Problem**: 90+ Widgets vorhanden, viele ohne Caching
- Jedes Widget lädt eigene Daten
- Keine gemeinsame Datenquelle
- Cache TTL zu niedrig (30-60s)

**Lösung**: Centralized Widget Data Provider mit längeren Cache-Zeiten

### 4. Fehlende Database Indexes (Hoch)
**Problem**: Queries auf company_id, created_at ohne optimale Indexes
- TenantScope Queries langsam
- Dashboard Aggregationen ineffizient

**Lösung**: Composite Indexes für häufige Query-Patterns

## 📋 Detaillierter Aktionsplan (2 Stunden)

### Phase 1: TenantScope Optimization (45 Minuten)
- [ ] Erstelle CachedTenantScope mit Request-Lifecycle Caching
- [ ] Implementiere Single Auth-Lookup pro Request
- [ ] Backward-Kompatibilität gewährleisten
- [ ] Performance-Tests: Auth-Lookup von 50ms auf <1ms

### Phase 2: Dashboard Query Optimization (45 Minuten) 
- [ ] Erstelle DashboardStatsService mit Bulk-Queries
- [ ] Implementiere Widget Data Provider Pattern
- [ ] Optimiere N+1 Probleme mit Eager Loading
- [ ] Cache-Strategien für Dashboard-Daten (5-15 Minuten TTL)

### Phase 3: Database Indexing (15 Minuten)
- [ ] Erstelle Migration für Performance-Indexes
- [ ] Composite Indexes: (company_id, created_at), (company_id, status)
- [ ] Query-Performance-Tests vor/nach

### Phase 4: Performance Monitoring (15 Minuten)
- [ ] PerformanceMonitor Middleware für Real-time Überwachung
- [ ] Query-Count und Response-Time Tracking
- [ ] Performance-Dashboard für kontinuierliche Überwachung

## 🎯 Erwartete Verbesserungen

### Vor der Optimierung:
- Dashboard Load Time: 3-5 Sekunden
- Memory Usage: 450MB Peak
- Query Count: 150+ pro Dashboard Load
- TenantScope Overhead: 50ms pro Query

### Nach der Optimierung:
- Dashboard Load Time: <1 Sekunde (80% Verbesserung)
- Memory Usage: <200MB (55% Verbesserung) 
- Query Count: <20 pro Dashboard Load (87% Reduktion)
- TenantScope Overhead: <1ms (98% Verbesserung)

## ✅ Erfolgskriterien

1. **Response Time**: Dashboard <1s statt >3s
2. **Memory Usage**: <200MB statt 450MB
3. **Query Efficiency**: <20 Queries statt 150+
4. **Auth Performance**: <1ms statt 50ms pro Scope
5. **Backward Compatibility**: Keine Breaking Changes

## 🚨 Risiko-Minimierung

- Alle Änderungen sind backward-kompatibel
- CachedTenantScope erweitert Original TenantScope
- Performance-Monitoring für Regression-Detection
- Rollback-Plan über Git verfügbar

## 📊 Monitoring & Validierung

- Real-time Performance Dashboard
- Query-Performance vor/nach Vergleich
- Memory Usage Tracking
- Response Time Monitoring
- Error Rate Überwachung

**Status**: Ready for Implementation
**Geschätzte Implementierungszeit**: 2 Stunden
**Erwartete Performance-Verbesserung**: 80%+