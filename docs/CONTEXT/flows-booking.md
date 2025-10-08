# Flows – Booking Core
1) Erkennen: Telefonnummer → Kunde eindeutig? ja→weiter | nein→Klärung
2) Servicewahl: count==1→auto, sonst Kurzfrage (Name+Preis+Dauer)
3) Staff-Wunsch optional (Name→Skillcheck→Slots staffId)
4) Zeitverständnis: „dieser/nächster Freitag“, TZ Europe/Berlin
5) Bestätigen: kurze Verifikation vor Buchung
6) Buchen: Cal.com v2 → persist → bestätigen
7) Änderung/Storno: nur mit Telefonnummer; Policies prüfen; Sync beidseitig
8) Rückrufbitte: erfassen + benachrichtigen
