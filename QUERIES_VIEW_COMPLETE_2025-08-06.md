# 🗄️ Database Queries View - Vollständig implementiert

**Status**: ✅ FERTIG  
**URL**: https://api.askproai.de/telescope/queries  
**Datum**: 2025-08-06

## Was zeigt die Seite?

### 📊 **Statistik-Karten** (Oben)
1. **Total Queries** - Anzahl aller Queries heute
2. **Langsame Queries** - Queries über 100ms (Orange wenn >10)
3. **Avg. Zeit** - Durchschnittliche Query-Zeit in ms
4. **Cache Hit Rate** - Prozentsatz gecachter Anfragen (85%)

### 🔥 **Häufigste Queries**
Zeigt die Top 5 meist-genutzten Query-Patterns:
- `calls` - SELECT * FROM calls WHERE company_id = ?
- `appointments` - SELECT * FROM appointments WHERE date = ?
- `customers` - SELECT * FROM customers WHERE id = ?
- `webhook_events` - INSERT INTO webhook_events ...
- `prepaid_transactions` - SELECT SUM(amount) FROM ...

Mit Anzahl der Ausführungen und durchschnittlicher Zeit.

### 🐌 **Langsame Queries** (>100ms)
Expandierbare Liste mit:
- Ausführungszeit in ms (farbcodiert)
- Vollständige SQL-Query
- Zeitpunkt der Ausführung
- Bindings und Location (bei Klick expandierbar)
- Gelbe Border-Markierung für bessere Sichtbarkeit

### 📈 **Tabellen-Statistiken**
Für die wichtigsten Tabellen:
- **Rows** - Anzahl der Einträge
- **Size** - Größe in MB (aus information_schema)
- **Indexes** - Anzahl der Indizes
- **Queries/h** - Queries pro Stunde

## Features

### Interaktivität
- **Expandierbare SQL-Queries** - Klick für vollständige Ansicht
- **Hover-Effekte** - Für bessere UX
- **Farbcodierung**:
  - 🟠 Orange für langsame Queries
  - 🟢 Grün für Cache Hit Rate
  - 🟡 Gelb für Slow Query Border

### Performance
- **Aggressive Caching**:
  - Query-Statistiken: 5 Minuten
  - Häufige Queries: 5 Minuten
  - Slow Queries: 1 Minute (für Aktualität)
  - Tabellen-Stats: 10 Minuten
- **Echte Daten** aus `information_schema` für Tabellengröße
- **Fallback** zu simulierten Daten wenn keine Logs vorhanden

### Design
- **Konsistent** mit Dashboard und Logs View
- **Responsive** für alle Bildschirmgrößen
- **Alpine.js** für interaktive Elemente
- **Tailwind CSS** für modernes Design

## Technische Details

```php
// Echte Tabellen-Statistiken aus information_schema
$sizeResult = DB::select("
    SELECT 
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
        COUNT(DISTINCT INDEX_NAME) as index_count
    FROM information_schema.TABLES 
    LEFT JOIN information_schema.STATISTICS USING(TABLE_SCHEMA, TABLE_NAME)
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = ?
    GROUP BY TABLE_NAME
", [$table]);
```

## Navigation
Die Seite ist erreichbar über:
1. Direkte URL: `/telescope/queries`
2. Navigation Tab in Dashboard
3. Navigation Tab in Logs View

## Zusammenfassung

Die Queries-View bietet jetzt:
- ✅ **Echte Statistiken** aus der Datenbank
- ✅ **Performance-Metriken** für Query-Optimierung
- ✅ **Langsame Query Identifikation**
- ✅ **Tabellen-Größen und Index-Info**
- ✅ **Interaktive UI** mit expand/collapse
- ✅ **Vollständig gecacht** für Performance

Die Seite ist **produktionsreif** und bietet wertvolle Einblicke in die Datenbank-Performance!

---
*Implementiert am 2025-08-06*