# Service Level Objectives
## Verfügbarkeit
- Admin (/admin): SLO 99.9% mtl.
- Doku (/askproai-complete-documentation.html): SLO 99.95% mtl.
## Fehlerbudget
- 43.2 min/Monat (99.9), 21.6 min/Monat (99.95).
## Messung
- Smoke-Cron (5‑min), Final-Gate (täglich 06:00). Abweichungen führen zu Incident.
## Alarmierung
- 2 aufeinanderfolgende Fehlschläge = Warnung, 6 = Incident P2.
