# Architektur (High-Level)
Clients (Telefon/Voice) ↔ Retell ↔ API-Gateway (Laravel) ↔ Cal.com v2 / Stripe / DB
Kerne:
- Webhooks: Cal.com, Stripe → Gateway (DLQ + Replay)
- Ledger: sekundengenau, idempotent
- Entity-Lifecycle: active/disabled/pending_delete/archived
- Sync: local_to_cal + cal_to_local, ≤10 s
- Observability: KPIs + Alerts
