# E2E Testplan (Kernauswahl)

## Telefon/Booking
1. Bekannte Nummer → Buchung Service single; Jahr nicht angesagt.
2. Bekannte Nummer → Wunsch-Staff; Skill vorhanden → Slot OK.
3. Bekannte Nummer → zwei Termine in einem Call (Kunde + Partner).
4. Buchung für Dritte mit bekannter Nummer (Mehrpersonen-Mapping).
5. Unbekannte Nummer → Neu-Kunde → Buchung erlaubt.
6. Unbekannte Nummer → Änderung versucht → blockiert (ASK-088).

## Änderung/Storno
7. Änderung mit bekannter Nummer → Treffer + Policy-Check.
8. Storno innerhalb Frist → OK; außerhalb → klare Voice-Ablehnung.

## Zeitverständnis
9. „Diesen Freitag 10 Uhr“ vs „nächsten Freitag“ → korrekt (Europe/Berlin).

## Sync
10. Änderung in Cal.com → DB in ≤10 s aktualisiert.
11. Änderung via Telefon → Cal.com in ≤10 s aktualisiert.
12. Konflikt parallel → „latest wins“.

## Composite
13. Composite A-Gap-C gebucht → Zeiten korrekt in Cal.com und DB.
14. Umbuchung Composite → Segmente konsistent.

## Rückrufbitten
15. „Bitte Rückruf“ → Eintrag, Benachrichtigung raus.

## Performance
16. Verfügbarkeits-Check p95 ≤2 s.
