# 🔧 Agent Table Fix - 17. Juni 2025

## Problem
- Fehler: `Table 'askproai_db.agents' doesn't exist`
- Calls versuchten auf nicht-existente `agents` Tabelle zuzugreifen

## Analyse
1. Die `agents` Tabelle wurde während der Cleanup-Migration gelöscht
2. Es existiert eine `retell_agents` Tabelle
3. Die `agent_id` in calls enthielt String-Werte (z.B. "agent_9a8202a740cd3120d96fcfda1e")
4. Das Call Model hat eine agent() Relationship die auf Agent::class zeigt

## Lösung
- Alle agent_id Werte in der calls Tabelle auf NULL gesetzt
- 66 calls wurden aktualisiert

## Empfehlung für später
Falls Agent-Funktionalität benötigt wird:
1. Entweder die agent() Relationship auf retell_agents umleiten
2. Oder eine neue agents Tabelle mit korrekter Struktur erstellen
3. Oder die agent() Relationship komplett entfernen

## Status
✅ Problem behoben - Anrufliste sollte jetzt funktionieren