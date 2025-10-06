<?php

return [
    // Hauptbezeichnungen
    'title' => 'Kunden',
    'singular' => 'Kunde',
    'plural' => 'Kunden',
    'navigation_label' => 'Kunden',
    'navigation_group' => 'Verwaltung',

    // Felder
    'name' => 'Name',
    'first_name' => 'Vorname',
    'last_name' => 'Nachname',
    'email' => 'E-Mail',
    'phone' => 'Telefon',
    'mobile' => 'Mobil',
    'address' => 'Adresse',
    'street' => 'Straße',
    'house_number' => 'Hausnummer',
    'postal_code' => 'PLZ',
    'city' => 'Stadt',
    'state' => 'Bundesland',
    'country' => 'Land',
    'company' => 'Firma',
    'company_name' => 'Firmenname',
    'vat_number' => 'USt-IdNr.',
    'notes' => 'Notizen',
    'status' => 'Status',
    'active' => 'Aktiv',
    'inactive' => 'Inaktiv',
    'created_at' => 'Erstellt am',
    'updated_at' => 'Aktualisiert am',
    'date_of_birth' => 'Geburtsdatum',
    'gender' => 'Geschlecht',
    'male' => 'Männlich',
    'female' => 'Weiblich',
    'diverse' => 'Divers',

    // Beziehungen
    'appointments' => 'Termine',
    'appointments_count' => 'Anzahl Termine',
    'invoices' => 'Rechnungen',
    'services' => 'Dienstleistungen',
    'bookings' => 'Buchungen',
    'total_revenue' => 'Gesamtumsatz',

    // Tabs
    'tab_general' => 'Allgemein',
    'tab_contact' => 'Kontakt',
    'tab_address' => 'Adresse',
    'tab_appointments' => 'Termine',
    'tab_history' => 'Historie',
    'tab_notes' => 'Notizen',

    // Aktionen
    'create' => 'Kunde anlegen',
    'edit' => 'Kunde bearbeiten',
    'delete' => 'Kunde löschen',
    'view' => 'Kunde anzeigen',
    'export' => 'Kunden exportieren',
    'import' => 'Kunden importieren',
    'merge' => 'Kunden zusammenführen',

    // Bulk-Aktionen
    'bulk_delete' => 'Ausgewählte löschen',
    'bulk_export' => 'Ausgewählte exportieren',
    'bulk_activate' => 'Aktivieren',
    'bulk_deactivate' => 'Deaktivieren',

    // Filter
    'filter_active' => 'Nur aktive',
    'filter_inactive' => 'Nur inaktive',
    'filter_with_appointments' => 'Mit Terminen',
    'filter_new' => 'Neue Kunden',
    'filter_vip' => 'VIP-Kunden',
    'filter_date_range' => 'Zeitraum',

    // Meldungen
    'created' => 'Kunde wurde erfolgreich angelegt',
    'updated' => 'Kunde wurde erfolgreich aktualisiert',
    'deleted' => 'Kunde wurde erfolgreich gelöscht',
    'not_found' => 'Kunde nicht gefunden',
    'has_appointments' => 'Kunde hat noch offene Termine',
    'confirm_delete' => 'Möchten Sie diesen Kunden wirklich löschen?',
    'no_customers' => 'Keine Kunden vorhanden',

    // Validierung
    'validation' => [
        'email_required' => 'E-Mail ist erforderlich',
        'email_unique' => 'Diese E-Mail-Adresse wird bereits verwendet',
        'phone_required' => 'Telefonnummer ist erforderlich',
        'name_required' => 'Name ist erforderlich',
        'invalid_email' => 'Ungültige E-Mail-Adresse',
    ],

    // Statistiken
    'stats_new_this_month' => 'Neue Kunden diesen Monat',
    'stats_total' => 'Kunden gesamt',
    'stats_active' => 'Aktive Kunden',
    'stats_revenue' => 'Umsatz',
];