# AskProAI – Claude Brief (Prioritäten, Guardrails, DoD)

## Ziel
Telefon-KI stabil live: Buchen | Ändern | Verschieben | Stornieren | Rückrufbitten. Natürliche Gespräche. Sichere Syncs mit Cal.com. Prepaid-Billing funktionsfähig. Keine Regression.

## Prioritäten (A→D)
A. Kernfunktion Telefonie & Buchung: ASK-001, 002, 005, 006, 009, 012, 022, 025, 038
B. Daten/Admin/Policies: ASK-010, 011, 013–016, 018–020, 023–024
C. Billing/Compliance/Monitoring: ASK-031–037, 026–027, 039–040
D. Prozess/Deployment: ASK-044–061
Zusätze: Kontext & 360-Views: ASK-062–067; Composite/Sync/Bugs/Callback: ASK-086–095; ROI/Monetarisierung: ASK-068–073; Kontext-Management: ASK-074–085.

## Nicht verhandelbare Invarianten
- Erkennung per Telefonnummer bleibt erhalten. Keine Namensabfrage, wenn Nummer eindeutig (Company/Branch-Scope).
- Ohne Telefonnummer **keine** Terminänderung/Storno (nur Neubuchung) – ASK-088.
- Mehrpersonen-Nummern sauber modellieren (eine Nummer → mehrere Kunden) – ASK-063.
- Natürliche Sprache: keine Doppelabfragen; Jahr nur ansagen, wenn ≠ aktuelles Jahr – ASK-005.
- Jeder Cal.com-Change in ≤10 s bei uns sichtbar und umgekehrt – ASK-089.
- Prepaid-Gating: kein Call ohne Reserve; Auto-Top-Up vorhanden – ASK-032/033.

## Definition of Done (Kernfunktion, Live)
- p95 Gesamtlatenz ≤2 s (Verfügbarkeitsprüfung + Buchung) – ASK-001.
- 30 E2E-Tests grün: Buchung/Änderung/Storno/Staff-Präferenz/Policies/„dieser vs. nächster Freitag“ – ASK-022.
- Bidirektionaler Sync Cal.com↔DB stabil, konfliktfrei – ASK-089/093.
- Rückrufbitten end-to-end erfasst, sichtbar, benachrichtigt – ASK-092/026.
- Billing: Ledger korrekt, Auto-Top-Up aktiv, Dunning vorhanden – ASK-031–035.
- Keine Datenlecks: Mandanten-Scopes, Audit-Logs – ASK-027/043.

## Arbeitsmodus
- Pro Feature: Spec → Implement → Tests → Verify → Deploy → Monitor – ASK-045.
- Jede Änderung referenziert ASK-IDs. PR blocked, wenn E2E rot – ASK-049.
- Staging vor Live, getrennte Keys/Daten – ASK-055–056.
