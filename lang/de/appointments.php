<?php

return [
    // Hauptbezeichnungen
    'title' => 'Termine',
    'singular' => 'Termin',
    'plural' => 'Termine',
    'navigation_label' => 'Termine',
    'navigation_group' => 'Kalender',

    // Felder
    'title_field' => 'Titel',
    'description' => 'Beschreibung',
    'customer' => 'Kunde',
    'customer_name' => 'Kundenname',
    'service' => 'Dienstleistung',
    'staff' => 'Mitarbeiter',
    'staff_member' => 'Mitarbeiter',
    'location' => 'Standort',
    'branch' => 'Filiale',
    'start_time' => 'Startzeit',
    'end_time' => 'Endzeit',
    'date' => 'Datum',
    'time' => 'Uhrzeit',
    'duration' => 'Dauer',
    'duration_minutes' => 'Dauer (Minuten)',
    'status' => 'Status',
    'notes' => 'Notizen',
    'internal_notes' => 'Interne Notizen',
    'price' => 'Preis',
    'paid' => 'Bezahlt',
    'payment_status' => 'Zahlungsstatus',
    'reminder_sent' => 'Erinnerung gesendet',
    'confirmed' => 'Bestätigt',
    'cancelled_at' => 'Storniert am',
    'cancelled_by' => 'Storniert von',
    'cancellation_reason' => 'Stornierungsgrund',
    'created_at' => 'Erstellt am',
    'updated_at' => 'Aktualisiert am',

    // Status-Optionen
    'status_pending' => 'Ausstehend',
    'status_confirmed' => 'Bestätigt',
    'status_completed' => 'Abgeschlossen',
    'status_cancelled' => 'Storniert',
    'status_no_show' => 'Nicht erschienen',
    'status_rescheduled' => 'Verschoben',

    // Zahlungsstatus
    'payment_pending' => 'Zahlung ausstehend',
    'payment_paid' => 'Bezahlt',
    'payment_partial' => 'Teilweise bezahlt',
    'payment_refunded' => 'Erstattet',

    // Zeitslots
    'morning' => 'Vormittag',
    'afternoon' => 'Nachmittag',
    'evening' => 'Abend',
    'available' => 'Verfügbar',
    'unavailable' => 'Nicht verfügbar',
    'booked' => 'Gebucht',

    // Tabs
    'tab_general' => 'Allgemein',
    'tab_customer' => 'Kunde',
    'tab_service' => 'Dienstleistung',
    'tab_payment' => 'Zahlung',
    'tab_notes' => 'Notizen',
    'tab_history' => 'Historie',

    // Aktionen
    'create' => 'Termin anlegen',
    'edit' => 'Termin bearbeiten',
    'delete' => 'Termin löschen',
    'view' => 'Termin anzeigen',
    'confirm' => 'Termin bestätigen',
    'cancel' => 'Termin stornieren',
    'reschedule' => 'Termin verschieben',
    'duplicate' => 'Termin duplizieren',
    'send_reminder' => 'Erinnerung senden',
    'send_confirmation' => 'Bestätigung senden',
    'mark_completed' => 'Als abgeschlossen markieren',
    'mark_no_show' => 'Als nicht erschienen markieren',

    // Bulk-Aktionen
    'bulk_delete' => 'Ausgewählte löschen',
    'bulk_confirm' => 'Ausgewählte bestätigen',
    'bulk_cancel' => 'Ausgewählte stornieren',
    'bulk_send_reminders' => 'Erinnerungen senden',

    // Filter
    'filter_today' => 'Heute',
    'filter_tomorrow' => 'Morgen',
    'filter_this_week' => 'Diese Woche',
    'filter_next_week' => 'Nächste Woche',
    'filter_this_month' => 'Diesen Monat',
    'filter_pending' => 'Ausstehende',
    'filter_confirmed' => 'Bestätigte',
    'filter_cancelled' => 'Stornierte',
    'filter_completed' => 'Abgeschlossene',
    'filter_staff' => 'Nach Mitarbeiter',
    'filter_service' => 'Nach Dienstleistung',
    'filter_customer' => 'Nach Kunde',

    // Kalender
    'calendar_view' => 'Kalenderansicht',
    'list_view' => 'Listenansicht',
    'day_view' => 'Tagesansicht',
    'week_view' => 'Wochenansicht',
    'month_view' => 'Monatsansicht',
    'all_day' => 'Ganztägig',

    // Meldungen
    'created' => 'Termin wurde erfolgreich angelegt',
    'updated' => 'Termin wurde erfolgreich aktualisiert',
    'deleted' => 'Termin wurde erfolgreich gelöscht',
    'confirmed' => 'Termin wurde bestätigt',
    'cancelled' => 'Termin wurde storniert',
    'rescheduled' => 'Termin wurde verschoben',
    'reminder_sent' => 'Erinnerung wurde gesendet',
    'no_appointments' => 'Keine Termine vorhanden',
    'conflict' => 'Terminkonflikt vorhanden',
    'confirm_delete' => 'Möchten Sie diesen Termin wirklich löschen?',
    'confirm_cancel' => 'Möchten Sie diesen Termin wirklich stornieren?',

    // Validierung
    'validation' => [
        'date_required' => 'Datum ist erforderlich',
        'time_required' => 'Uhrzeit ist erforderlich',
        'customer_required' => 'Kunde ist erforderlich',
        'service_required' => 'Dienstleistung ist erforderlich',
        'staff_required' => 'Mitarbeiter ist erforderlich',
        'time_conflict' => 'Zeitkonflikt mit anderem Termin',
        'invalid_date' => 'Ungültiges Datum',
        'past_date' => 'Datum liegt in der Vergangenheit',
    ],

    // Erinnerungen
    'reminder_1_day' => 'Erinnerung 1 Tag vorher',
    'reminder_2_days' => 'Erinnerung 2 Tage vorher',
    'reminder_1_week' => 'Erinnerung 1 Woche vorher',
    'reminder_custom' => 'Benutzerdefinierte Erinnerung',

    // Statistiken
    'stats_today' => 'Termine heute',
    'stats_this_week' => 'Termine diese Woche',
    'stats_pending' => 'Ausstehende Termine',
    'stats_revenue' => 'Erwarteter Umsatz',
];