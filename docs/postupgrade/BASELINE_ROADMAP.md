# PHPStan Baseline Abbau – Roadmap
Ziel: Baseline auf 0 in Iterationen, ohne Produktivrisiko.

## Prinzipien
- Fix-first (Security, Runtime-Risiken, DB/Cache/Queue).
- Pro PR max. 200 reduzierte Findings.
- Guard verhindert Wachstum.

## Iteration 1
- Kategorien: fehlende Typehints, nullability.
- Ziel: -500 Findings.

## Iteration 2
- Dead code, ungenutzte Importe.
- Ziel: -600 Findings.

## Iteration 3
- Mixed/array-shapes präzisieren, DTOs einführen.
- Ziel: -800 Findings.

## Erfolgsmessung
- SUM_LIMIT in `phpstan-baseline.limit` pro PR reduzieren.
- Guard bleibt aktiv.
