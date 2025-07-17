# 🔧 Staff is_active Column Fix - Zusammenfassung

## Problem
Beim Öffnen der Company-Detailseite trat folgender Fehler auf:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_active' in 'WHERE'
```

## Ursache
Die ViewCompany-Seite versuchte, aktive Mitarbeiter zu zählen mit:
```php
->state(fn ($record) => $record->staff()->where('is_active', true)->count())
```

Aber die Spalte `is_active` existierte nicht in der `staff` Tabelle.

## Lösung

### 1. **Migration erstellt**
- Neue Migration: `2025_01_16_add_is_active_to_staff_table.php`
- Fügt `is_active` boolean Spalte hinzu (default: true)
- Erstellt Index für Performance

### 2. **Model aktualisiert**
- `is_active` zu $fillable array hinzugefügt
- `is_active` zu $casts array hinzugefügt (als boolean)

### 3. **Migration ausgeführt**
```bash
php artisan migrate --force
```

## Ergebnis
✅ **Problem behoben!**
- Spalte erfolgreich hinzugefügt
- Alle 5 vorhandenen Mitarbeiter sind aktiv
- Company-Detailseite funktioniert wieder

## Hinweise
- Die Spalte `active` existiert bereits, aber `is_active` wird zusätzlich verwendet
- Möglicherweise sollte langfristig nur eine der beiden Spalten verwendet werden
- Alle bestehenden Mitarbeiter wurden automatisch als aktiv markiert

Die Company-Detailseite sollte jetzt ohne Fehler aufrufbar sein!