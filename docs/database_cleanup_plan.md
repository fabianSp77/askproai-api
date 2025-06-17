# 🗑️ Datenbank-Bereinigungsplan

## 📊 Aktuelle Situation: 119 Tabellen

### 🔴 ZU LÖSCHENDE TABELLEN (99 Tabellen)

#### 1. Reservation System (12 Tabellen) - KOMPLETT ENTFERNEN
```sql
reservation_accessories
reservation_color_rules
reservation_files
reservation_guests
reservation_instances
reservation_reminders
reservation_resources
reservation_series
reservation_statuses
reservation_types
reservation_users
reservation_waitlist_requests
```

#### 2. Resource Management (14 Tabellen) - NICHT BENÖTIGT
```sql
resources
resource_accessories
resource_group_assignment
resource_groups
resource_status_reasons
resource_type_assignment
resource_types
accessories
blackout_instances
blackout_series
blackout_series_resources
peak_times
quotas
schedules
```

#### 3. OAuth System (5 Tabellen) - NICHT VERWENDET
```sql
oauth_access_tokens
oauth_auth_codes
oauth_clients
oauth_personal_access_clients
oauth_refresh_tokens
```

#### 4. Announcement System (3 Tabellen) - NICHT BENÖTIGT
```sql
announcements
announcement_groups
announcement_resources
```

#### 5. Custom Attributes (4 Tabellen) - ZU KOMPLEX FÜR MVP
```sql
custom_attributes
custom_attribute_categories
custom_attribute_entities
custom_attribute_values
```

#### 6. User Management Overkill (8 Tabellen)
```sql
user_email_preferences
user_groups
user_preferences
user_resource_permissions
user_session
user_statuses
group_resource_permissions
group_roles
```

#### 7. Redundante/Alte Tabellen (20+ Tabellen)
```sql
agents (verwenden branches.retell_agent_id)
account_activation
activity_log (zu detailliert)
api_health_logs (nicht genutzt)
business_hours_templates (in branches integriert)
calendar_mappings (veraltet)
calendars (veraltet)
conversion_targets
dashboard_configurations
dbversion
dummy_companies
event_type_mappings (veraltet)
kunden (Duplikat von customers)
laravel_users (Duplikat)
layouts
master_services
notes
notification_log
reseller_tenant
roles_old
saved_reports
slow_query_log
staff_branches_and_staff_services_tables
staff_service_assignments_backup
tenants
tests
time_blocks
validation_results
```

### ✅ KERN-TABELLEN DIE BLEIBEN (20 Tabellen)

#### 1. Firmen & Struktur (3)
- `companies` - Mandanten
- `branches` - Filialen/Standorte
- `phone_numbers` - Telefonnummern → Filialen

#### 2. Personen (3)
- `users` - System-Benutzer
- `staff` - Mitarbeiter
- `customers` - Kunden

#### 3. Termine & Anrufe (2)
- `appointments` - Termine
- `calls` - Anrufe

#### 4. Services & Zuordnungen (4)
- `services` - Dienstleistungen
- `staff_services` - Mitarbeiter ↔ Services
- `staff_event_types` - Mitarbeiter ↔ Cal.com Events
- `working_hours` - Arbeitszeiten

#### 5. Cal.com Integration (3)
- `calcom_event_types` - Event Types
- `calcom_bookings` - Buchungen
- `calcom_sync_logs` - Sync Status

#### 6. System (5)
- `migrations` - Laravel Migrations
- `jobs` - Queue Jobs
- `failed_jobs` - Fehlgeschlagene Jobs
- `cache` - Cache (optional)
- `cache_locks` - Cache Locks (optional)

#### 7. Zugriff & Sicherheit (3)
- `permissions` - Berechtigungen
- `roles` - Rollen
- `role_has_permissions` - Rollen ↔ Berechtigungen
- `model_has_roles` - Model ↔ Rollen
- `model_has_permissions` - Model ↔ Berechtigungen

#### 8. Bezahlung (später) (5)
- `invoices` - Rechnungen
- `billing_periods` - Abrechnungszeiträume
- `company_pricing` - Firmen-Preise
- `branch_pricing_overrides` - Filial-Preise
- `payments` (später)

### 📈 ERGEBNIS
- **Vorher**: 119 Tabellen
- **Nachher**: 20 Kern-Tabellen
- **Reduzierung**: 83% weniger Komplexität!