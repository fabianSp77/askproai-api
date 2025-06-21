# Event Type Naming Fix Summary

## Problem gelöst ✅

**Vorher:**
- Original: "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"
- Import: "AskProAI – Berlin-AskProAI-AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"

**Nachher:**
- Original: "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"
- Import: "Test Branch - Beratung"

## Lösung

### 1. SmartEventTypeNameParser erstellt
Intelligente Extraktion von Service-Namen aus Marketing-Texten:
- Entfernt Firmennamen (AskProAI, ModernHair, etc.)
- Entfernt Ortsangaben (Berlin, München, etc.)
- Entfernt Marketing-Phrasen (30% mehr Umsatz, 24/7, etc.)
- Erkennt Service-Keywords (Beratung, Termin, Behandlung, etc.)
- Fügt Zeitangaben sinnvoll hinzu (30 Min Beratung)

### 2. Verschiedene Namensformate
Der Parser bietet verschiedene Formate:
- **standard**: "Branch-Company-Service" (Original-Format)
- **compact**: "Branch - Service" (Empfohlen)
- **service_first**: "Service (Branch)"
- **full**: "Company Branch: Service"

### 3. EventTypeImportWizard Updated
- Nutzt jetzt den SmartEventTypeNameParser
- Fallback auf alten Parser für Kompatibilität
- Zeigt verschiedene Namensoptionen im Import-Preview

## Beispiele

| Original | Alt (Problem) | Neu (Gelöst) |
|----------|---------------|--------------|
| AskProAI + aus Berlin + Beratung + 30% mehr Umsatz... | Berlin-AskProAI-AskProAI + aus Berlin... | Berlin - Beratung |
| 30 Minuten Termin mit Fabian Spitzer | Berlin-AskProAI-30 Minuten Termin mit Fabian Spitzer | Berlin - 30 Min Termin |
| ModernHair - Haarschnitt Herren | Berlin-AskProAI-ModernHair Haarschnitt Herren | Berlin - Haarschnitt |
| FitXpert München - Personal Training 60 Min | Berlin-AskProAI-FitXpert München Personal... | Berlin - 60 Min Training |

## Vorteile
1. **Saubere, lesbare Namen** statt überlanger Marketing-Texte
2. **Konsistente Namensgebung** über alle Event-Types
3. **Flexibilität** durch verschiedene Formate
4. **Intelligente Erkennung** von Service-Typen und Zeitangaben

## Nächste Schritte
1. Testen Sie den Event-Type Import erneut
2. Die Namen sollten jetzt viel sauberer sein
3. Bei Bedarf können andere Namensformate gewählt werden