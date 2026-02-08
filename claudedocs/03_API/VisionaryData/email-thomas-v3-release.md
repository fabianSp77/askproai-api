# E-Mail an Thomas Stanner — IT-Support Agent v3.0

**An:** thomas.stanner@visionarydata.de
**CC:** sebastian.sager@visionarydata.de, sebastian.gesellensetter@visionarydata.de
**Betreff:** IT-Support Agent v3.0 — Dein Feedback umgesetzt

---

Hallo Thomas,

danke für dein ausführliches Feedback zum Fragekatalog. Ich habe alle Punkte aufgegriffen und in Version 3.0 des Agents umgesetzt. Hier die Übersicht, was sich geändert hat — jeweils mit Bezug auf dein Feedback:

## "So wenig Roboter-Verhör wie möglich"

| Dein Feedback | Vorher (v2.8) | Jetzt (v3.0) |
|---|---|---|
| *"Der deutsche IT-Kunde ruft an, weil er ein Problem hat, nicht um Formulare am Telefon auszufüllen."* | Strukturiert: "Nennen Sie zuerst die Firma, dann den Namen, dann das Problem..." | Offen: "Bitte schildern Sie mir kurz Ihr Problem. Worum geht es?" — Infos werden automatisch aus dem Gespräch erkannt. |
| *"Seit wann / Scope / Impact aus der harten Pflicht-Liste raus. Wenn der Kunde sagt 'Mein Drucker brennt', will ich nicht, dass der Bot fragt 'Seit wann brennt er?'"* | 8 Pflichtfelder als sequentielle Blöcke | 4 Pflichtfelder (Firma, Name, Problem, Rückruf). Standort, Scope, Impact, Seit-wann werden nur erfasst, wenn der Anrufer sie von sich aus nennt. |
| *"Max. 2 Rückfrage-Runden, dann Absprung zur Datenerfassung, um Loop-Frust zu vermeiden."* | Unbegrenzte Triage + danach noch Impact/Scope-Blöcke | Exakt 1 gezielte Rückfrage pro Kategorie, dann direkt zur Rückrufnummer. |
| *"Bei Ransomware, Erpressung, Datenleck → Keine weiteren Fragen, sofort Ticket erstellen und Eskalations-Flag setzen."* | Security wie jede andere Kategorie behandelt | Hard-Trigger: Triage wird komplett übersprungen, Ticket sofort mit höchster Priorität erstellt. |

## Deine Wording-Vorgaben — 1:1 übernommen

| Stelle | Deine Vorgabe | Status |
|---|---|---|
| Consent | "Willkommen beim IT-Support von [Firma]. Ich bin der digitale Assistent. Um Ihr Anliegen aufzunehmen..." | Drin |
| Einstieg | "Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem. Worum geht es?" | Drin |
| Rückruf | "Unter welcher Nummer können wir Sie für Rückfragen am besten erreichen?" | Drin |
| Abschluss | "Danke, ich habe das Ticket erstellt. Ein Techniker schaut sich das an und meldet sich in Kürze bei Ihnen." | Drin |
| Fallback | "Entschuldigung, die Verbindung war kurz schlecht. Können Sie das bitte wiederholen?" | Drin |

## Stimme

Dein ElevenLabs-Favorit ist jetzt aktiv im Agent hinterlegt.

## Bevor ich teste: Dein Input

Bevor ich in die Testphase gehe, würde ich gern dein Feedback zu zwei Dingen abwarten:

1. **Passt das so?** Schau dir die Änderungen oben nochmal in Ruhe an. Wenn dir noch was auffällt oder du etwas anders haben willst — jetzt ist der beste Zeitpunkt, das reinzugeben.
2. **Beispiel-Gespräche:** Wenn du möchtest, kannst du mir für die verschiedenen Kategorien (Netzwerk, M365, Drucker, Security etc.) konkrete Beispiel-Gespräche schicken — also wie ein typischer Anruf aus deiner Sicht ablaufen sollte. Das hilft mir, den Agent noch genauer auf eure Praxis abzustimmen und gezielter zu testen.

Sobald ich dein Feedback habe, gehe ich in den Test. Wenn bei mir alles passt, lasse ich dich nochmal selbst testen, bevor wir weitermachen.

Melde dich einfach, wenn du soweit bist.

Beste Grüße
Fabian
