# Phase 1 – Go-Live Kernfunktion (Ziel 2 Wochen)

## 1) Latenz & Dialog (ASK-001, 005, 006, 009)
- Profiling je Layer bauen (Retell RTT, Gateway, Cal.com)
- Prompt-Refactor: keine Doppelabfragen; Jahr nur bei Bedarf; „Ich prüfe…“ Streaming
- Zeit-Custom-Function: Wochentag + „dieser/nächster“
- Auto-Servicewahl bei count==1

## 2) Buchungs-Stabilität (ASK-002, 012, 022)
- Robust Lookup (bookingId + Fallback E-Mail/Telefon+Datum)
- Staff-Präferenz/Skillcheck + Slots staffId
- 30 E2E-Tests abdecken (auch „Buchung für Dritte“/zwei Termine in einem Call)

## 3) Sync & Regeln (ASK-088, 089, 093)
- Ohne Telefonnummer: Änderung/Storno blocken, klare Voice-Rückmeldung
- Bidirektionaler Sync Cal.com↔DB inkl. Webhook-Ingress + DLQ
- Dashboard „Sync-Erfolg %“, Alarm <99 %

## 4) Rückrufbitten (ASK-092, 026)
- Intent + DB + Benachrichtigung (E-Mail/In-App)
- Sicht in Admin-UI

## DoD Phase 1
- p95 ≤2 s; 30/30 E2E grün; Sync bidirektional OK; Rückrufbitte end-to-end.
