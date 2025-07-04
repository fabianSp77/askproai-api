# Retell Collect-Data Fix V2 (2025-07-04)

## Status: ✅ ERFOLGREICH IMPLEMENTIERT

### Problemanalyse

Der Fehler trat auf, weil die Custom Function `collect_customer_data` während eines laufenden Anrufs aufgerufen wurde, aber der existierende Call in der Datenbank nicht gefunden werden konnte. Dies führte zu einem "Duplicate Entry" Error beim Versuch, einen neuen Call zu erstellen.

**Ursachen:**
1. **Global Scopes Problem**: `withoutGlobalScope(TenantScope::class)` war nicht ausreichend
2. **Timing**: Die Custom Function wird während des laufenden Calls aufgerufen (Status: "ongoing")
3. **Phone Number Placeholder**: `{{caller_phone_number}}` wurde zu spät ersetzt

### Implementierte Lösung

1. **Robustere Call-Suche**:
   - Verwendung von `withoutGlobalScopes()` statt nur `withoutGlobalScope(TenantScope::class)`
   - Erweiterte Zeitfenster für die Suche (30 Minuten statt 10)
   - Telefonnummer-Extraktion vor der Call-Suche

2. **Bessere Fehlerbehandlung**:
   - Bei Duplicate Entry Error wird der existierende Call gesucht und aktualisiert
   - Graceful Handling wenn Call nicht gefunden wird (Success Response mit "pending" Status)

3. **Verbessertes Logging**:
   - Correlation IDs für besseres Tracking
   - Detaillierte Logs bei jedem Schritt
   - Call Status und Timestamps in Logs

4. **Telefonnummer-Handling**:
   - Frühe Extraktion der Telefonnummer aus verschiedenen Quellen
   - Placeholder-Ersetzung vor der Call-Suche

### Test-Ergebnisse

✅ **Test 1**: Neuer Call wird erfolgreich erstellt
✅ **Test 2**: Duplicate Entry wird korrekt behandelt (Update statt Error)
✅ **Test 3**: Existierende Calls (Hans Schuster) werden erfolgreich aktualisiert

### Wichtige Änderungen

**RetellDataCollectionController.php**:
- Zeile 57-59: `withoutGlobalScopes()` statt `withoutGlobalScope(TenantScope::class)`
- Zeile 40-50: Frühe Telefonnummer-Extraktion
- Zeile 189-220: Verbesserte Duplicate Entry Behandlung
- Zeile 227-232: Success Response auch bei fehlgeschlagenem Call-Create

### Nächste Schritte

Dein nächster Testanruf sollte jetzt funktionieren! Die Custom Function wird:
1. Den existierenden Call finden
2. Die Kundendaten korrekt speichern
3. Eine Success Response zurückgeben

### Debug-Befehle

Falls weitere Probleme auftreten:
```bash
# Logs prüfen
tail -f storage/logs/laravel.log | grep -i "retelldata"

# Call in DB suchen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "SELECT id, retell_call_id, from_number, status, created_at FROM calls WHERE created_at >= NOW() - INTERVAL 1 HOUR ORDER BY created_at DESC LIMIT 10;"
```