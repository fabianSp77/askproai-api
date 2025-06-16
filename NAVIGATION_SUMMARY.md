# Navigation Summary for AskProAI Admin Panel

## Navigation Groups Configured

The following navigation groups are configured in `AdminPanelProvider.php`:
1. **Event Management** - For event and appointment related features
2. **Stammdaten** - For master data (staff, services, etc.)
3. **Kundenverwaltung** - For customer management
4. **Monitoring & Status** - For system monitoring pages
5. **System** - For system administration and debugging

## Custom Pages and Their Navigation Settings

### Visible to All Admins:

1. **SimpleDashboard** (`/admin`)
   - Icon: heroicon-o-home
   - Label: Dashboard
   - Group: (none - appears at top)
   - Sort: -2 (appears first)

2. **Event Analytics Dashboard** (`/admin/event-analytics-dashboard`)
   - Icon: heroicon-o-chart-bar
   - Label: Analytics Dashboard
   - Group: Event Management
   - Sort: 5

3. **Event Type Import Wizard** (`/admin/event-type-import-wizard`)
   - Icon: heroicon-o-arrow-down-tray
   - Label: Event-Type Import
   - Group: Event Management
   - Sort: 3

4. **Staff Event Assignment** (`/admin/staff-event-assignment`)
   - Icon: heroicon-o-user-group
   - Label: Mitarbeiter-Zuordnung
   - Group: Event Management
   - Sort: 2

5. **Staff Event Assignment Modern** (`/admin/staff-event-assignment-modern`)
   - Icon: heroicon-o-sparkles
   - Label: Smart Zuordnung
   - Group: Event Management
   - Sort: 3

6. **System Cockpit** (`/admin/system-cockpit`)
   - Icon: heroicon-o-bolt
   - Label: System Cockpit
   - Group: Monitoring & Status
   - Sort: 0

### Visible to Super Admins Only:

1. **Security Dashboard** (`/admin/security-dashboard`)
   - Icon: heroicon-o-shield-check
   - Label: Security Dashboard (auto-generated)
   - Group: System
   - Sort: 1
   - Requires: `view_security_dashboard` permission

2. **System Status** (`/admin/system-status`)
   - Icon: heroicon-o-cpu-chip
   - Label: Systemstatus
   - Group: System
   - Sort: 2

### Conditional Visibility:

1. **Onboarding Wizard** (`/admin/onboarding`)
   - Icon: heroicon-o-academic-cap
   - Label: Einrichtungsassistent
   - Group: (none)
   - Sort: 0
   - Only visible if onboarding is not completed

### Debug Pages (Only in Debug Mode or for Super Admins):

1. **Debug** (`/admin/debug`)
   - Icon: heroicon-o-bug-ant
   - Group: (none)

2. **Debug Data** (`/admin/debug-data`)
   - Icon: heroicon-o-bug-ant
   - Group: System
   - Sort: 999

3. **Debug Dashboard** (`/admin/debug-dashboard`)
   - Icon: heroicon-o-wrench
   - Label: Debug
   - Group: System
   - Sort: 999

## Resource Pages (Not in Navigation)

The following are resource pages and don't appear in navigation:
- CreateCompany
- EditCompany
- ListCompanies
- Dashboard (appears to be disabled, using SimpleDashboard instead)

## Notes

- Pages auto-discovered from `app/Filament/Admin/Pages`
- Resources auto-discovered from `app/Filament/Admin/Resources`
- Widgets auto-discovered from `app/Filament/Admin/Widgets`
- Navigation groups help organize the sidebar menu
- Sort order determines position within groups (lower numbers appear first)
- Pages without groups appear at the top level