# Retell Agent Metriken (Oktober 2025)

- Die Admin-Oberfläche aggregiert Statistiken jetzt zur Laufzeit aus der `calls`-Tabelle (`RetellAgent::withPerformanceMetrics()`).
- Persistierte Spalten (`retell_agents.total_calls`, `successful_calls`, …) dienen nur noch als Fallback und werden nicht mehr aktiv gepflegt.
- Für performante Abfragen wurden Indizes auf `calls.agent_id` sowie `calls.agent_id, call_status` ergänzt.
- Der optionale `RetellAgentPerformanceService` kapselt die Abfrage und cached Ergebnisse standardmäßig für 5 Minuten.
- Tests: `tests/Unit/Models/RetellAgentPerformanceScopeTest.php` prüft Aggregation und Service-Caching.

## Verwendung

```php
$agents = RetellAgent::withPerformanceMetrics()->get();

$service = app(\App\Services\Retell\RetellAgentPerformanceService::class);
$metrics = $service->getMetrics($agentId);
```

## Offene Punkte

- Falls dauerhaft gespeicherte Statistiken benötigt werden, kann `agent_performance_metrics` über einen Nightly-Job befüllt werden.
- Legacy-Spalten können nach erfolgreicher Adoption entfernt oder historisch archiviert werden.
