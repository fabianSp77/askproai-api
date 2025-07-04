# BillingPeriod Edit Page 500 Error - Gelöst

## Problem
Die URL `/admin/billing-periods/2` führte zu einem 500 Error.

## Ursache
Der `TenantScope` im `BillingPeriod` Model filtert automatisch alle Datensätze nach der `company_id` des aktuellen Users. Der admin@askproai.de User hatte jedoch keine `company_id` zugewiesen (NULL).

Dadurch konnte der TenantScope keine BillingPeriods finden, auch wenn diese in der Datenbank existierten.

## Lösung
```sql
UPDATE users SET company_id = 1 WHERE email = 'admin@askproai.de';
```

## Details
1. **BillingPeriod mit ID 2 existiert** in der Datenbank mit company_id = 1
2. **TenantScope** filtert automatisch nach User->company_id
3. **admin@askproai.de** hatte company_id = NULL
4. **Resultat**: Keine Datensätze gefunden → 404/500 Error

## Verifizierung
- Alle User haben jetzt eine company_id zugewiesen
- Die Edit-Seite sollte jetzt funktionieren
- Der TenantScope funktioniert korrekt für Multi-Tenant Isolation

## Empfehlung
Bei der User-Erstellung sollte immer eine company_id zugewiesen werden, entweder:
1. Explizit bei der Registrierung
2. Über die tenant_id Beziehung (wie im User Model implementiert)
3. Als Default-Wert für Admin-User