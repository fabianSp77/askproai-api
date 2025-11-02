# E2E Deployment Flowchart

**Version:** 1.0
**Erstellt:** 2025-11-02
**Zweck:** Vollständiger Deployment-Flow von Build bis Production mit Gates

---

## Mermaid Flowchart

```mermaid
flowchart TD
  A([Merge to develop]) --> B[Build Artifacts\n9 Pre-Bundle Checks]
  B --> C{Build success?}
  C -- no --> X1[Stop & fix build]:::fail
  C -- yes --> D[Wait for artifacts ready]
  D --> E[[Deploy to Staging]]
  subgraph STAGING
    E --> F[Pre-switch gates\nindex.php, vendor/autoload.php, artisan]
    F --> G[Run migrations]
    G --> H[Switch symlink atomic]
    H --> I[Post-switch cache clear\nconfig/route/view]
    I --> J[Reload PHP-FPM OPcache & NGINX]
    J --> K[Grace wait ~15s]
    K --> L{Health 3/3 HTTP 200?\n/health Bearer, /api/health-check Bearer, /healthcheck.php}
    L -- no --> RB[Auto-rollback → previous]:::fail --> X2[Stop & investigate]:::fail
    L -- yes --> M[Optional: Staging Smoke 5/5]
  end
  M --> N{PROD-DEPLOY FREIGEGEBEN?}
  N -- no --> O([Done: Staging validated])
  N -- yes --> P[[Production Pre-Flight read-only]]
  subgraph PRODUCTION
    P --> Q{Pre-flight ok?}
    Q -- no --> X3[Abort Prod]:::fail
    Q -- yes --> R[Deploy to Production\nswitch symlink]
    R --> S[Reload services + Grace]
    S --> T{Prod health 2/2 200?\n/health + manifest}
    T -- no --> RB2[Auto-rollback]:::fail
    T -- yes --> U([Done: Prod live])
  end
  classDef fail fill:#FDE8E8,stroke:#E11D48,color:#7F1D1D;
```

---

## Verwendung

Dieser Flowchart zeigt den vollständigen E2E-Deployment-Prozess:

### Phase 1: Build
- **Pre-Bundle Gates (Layer 1):** 9 kritische Checks vor Bundle-Erstellung
- **Artifact Upload:** 30 Tage Retention mit SHA256

### Phase 2: Staging Deployment
- **Pre-Switch Gates (Layer 2):** 9 Checks + PHP Tests VOR Migrations
- **Post-Switch Hardening:** Cache Clear + PHP-FPM Reload + Grace Period
- **Health Checks:** Retry-Logik (6 Attempts, 5s Intervall)
- **Auto-Rollback:** Bei Health-Failure

### Phase 3: Production (nach Freigabe)
- **Pre-Flight:** Read-only Validierung
- **Pre-Switch Gates (Layer 3):** 9 Checks + PHP Tests VOR Symlink
- **Zero-Downtime:** Atomic Symlink Switch (< 1s)
- **Auto-Rollback:** Bei Smoke-Test-Failure

---

## Abbruchkriterien

| Punkt | Aktion | Recovery |
|-------|--------|----------|
| Build Failure | Stop | Fix Code |
| Pre-Switch Gate Fail | Abort Deploy | Fix Bundle/Config |
| Health Check Fail | Auto-Rollback | Investigate Logs |
| Prod Pre-Flight Fail | NO-GO | Fix Staging First |

---

## Referenzen

- **Handbuch:** [DEPLOYMENT_HANDBUCH_FUER_DRITTE.html](https://api.askproai.de/docs/backup-system/DEPLOYMENT_HANDBUCH_FUER_DRITTE.html)
- **Status Quo:** [status-quo-deployment-prozess-2025-11-01.html](https://api.askproai.de/docs/backup-system/status-quo-deployment-prozess-2025-11-01.html)
- **E2E Validation:** [E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html](https://api.askproai.de/docs/backup-system/E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html)

---

**Wartung:** Bei Prozess-Änderungen dieses Flowchart aktualisieren.
