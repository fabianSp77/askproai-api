<?php

namespace App\Helpers;

/**
 * Zentrale Deutsche Übersetzungen für Konsistenz
 */
class GermanTranslations
{
    /**
     * Business-Begriffe
     */
    const BUSINESS_TERMS = [
        // Rollen & Entitäten
        'reseller' => 'Mandant',
        'Reseller' => 'Mandant',
        'resellers' => 'Mandanten',
        'Resellers' => 'Mandanten',
        'customer' => 'Kunde',
        'Customer' => 'Kunde',
        'customers' => 'Kunden',
        'Customers' => 'Kunden',
        'company' => 'Unternehmen',
        'Company' => 'Unternehmen',
        'companies' => 'Unternehmen',
        'Companies' => 'Unternehmen',
        'staff' => 'Mitarbeiter',
        'Staff' => 'Mitarbeiter',
        'branch' => 'Filiale',
        'Branch' => 'Filiale',
        'branches' => 'Filialen',
        'Branches' => 'Filialen',
        'agent' => 'Agent',
        'Agent' => 'Agent',
        'user' => 'Benutzer',
        'User' => 'Benutzer',
        'users' => 'Benutzer',
        'Users' => 'Benutzer',
        'tenant' => 'Mandant',
        'Tenant' => 'Mandant',
        'partner' => 'Partner',
        'Partner' => 'Partner',

        // Kosten-Begriffe
        'cost' => 'Kosten',
        'Cost' => 'Kosten',
        'costs' => 'Kosten',
        'Costs' => 'Kosten',
        'base_cost' => 'Basiskosten',
        'base cost' => 'Basiskosten',
        'Base Cost' => 'Basiskosten',
        'reseller_cost' => 'Mandanten-Kosten',
        'reseller cost' => 'Mandanten-Kosten',
        'Reseller Cost' => 'Mandanten-Kosten',
        'customer_cost' => 'Kundenkosten',
        'customer cost' => 'Kundenkosten',
        'Customer Cost' => 'Kundenkosten',
        'price' => 'Preis',
        'Price' => 'Preis',
        'amount' => 'Betrag',
        'Amount' => 'Betrag',
        'balance' => 'Guthaben',
        'Balance' => 'Guthaben',
        'credit' => 'Guthaben',
        'Credit' => 'Guthaben',
        'invoice' => 'Rechnung',
        'Invoice' => 'Rechnung',
        'transaction' => 'Transaktion',
        'Transaction' => 'Transaktion',
    ];

    /**
     * Anruf-Begriffe
     */
    const CALL_TERMS = [
        'call' => 'Anruf',
        'Call' => 'Anruf',
        'calls' => 'Anrufe',
        'Calls' => 'Anrufe',
        'inbound' => 'Eingehend',
        'Inbound' => 'Eingehend',
        'outbound' => 'Ausgehend',
        'Outbound' => 'Ausgehend',
        'duration' => 'Dauer',
        'Duration' => 'Dauer',
        'status' => 'Status',
        'Status' => 'Status',
        'completed' => 'Abgeschlossen',
        'Completed' => 'Abgeschlossen',
        'missed' => 'Verpasst',
        'Missed' => 'Verpasst',
        'failed' => 'Fehlgeschlagen',
        'Failed' => 'Fehlgeschlagen',
        'busy' => 'Besetzt',
        'Busy' => 'Besetzt',
        'no answer' => 'Keine Antwort',
        'No Answer' => 'Keine Antwort',
        'appointment' => 'Termin',
        'Appointment' => 'Termin',
        'appointments' => 'Termine',
        'Appointments' => 'Termine',
        'notes' => 'Notizen',
        'Notes' => 'Notizen',
        'recording' => 'Aufnahme',
        'Recording' => 'Aufnahme',
        'transcript' => 'Transkript',
        'Transcript' => 'Transkript',
        'summary' => 'Zusammenfassung',
        'Summary' => 'Zusammenfassung',
    ];

    /**
     * Aktions-Begriffe
     */
    const ACTION_TERMS = [
        'create' => 'Erstellen',
        'Create' => 'Erstellen',
        'new' => 'Neu',
        'New' => 'Neu',
        'edit' => 'Bearbeiten',
        'Edit' => 'Bearbeiten',
        'update' => 'Aktualisieren',
        'Update' => 'Aktualisieren',
        'delete' => 'Löschen',
        'Delete' => 'Löschen',
        'remove' => 'Entfernen',
        'Remove' => 'Entfernen',
        'save' => 'Speichern',
        'Save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'Cancel' => 'Abbrechen',
        'confirm' => 'Bestätigen',
        'Confirm' => 'Bestätigen',
        'export' => 'Exportieren',
        'Export' => 'Exportieren',
        'import' => 'Importieren',
        'Import' => 'Importieren',
        'download' => 'Herunterladen',
        'Download' => 'Herunterladen',
        'upload' => 'Hochladen',
        'Upload' => 'Hochladen',
        'search' => 'Suchen',
        'Search' => 'Suchen',
        'filter' => 'Filtern',
        'Filter' => 'Filtern',
        'view' => 'Anzeigen',
        'View' => 'Anzeigen',
        'show' => 'Zeigen',
        'Show' => 'Zeigen',
        'hide' => 'Verbergen',
        'Hide' => 'Verbergen',
        'refresh' => 'Aktualisieren',
        'Refresh' => 'Aktualisieren',
        'reload' => 'Neu laden',
        'Reload' => 'Neu laden',
    ];

    /**
     * UI-Elemente
     */
    const UI_TERMS = [
        'dashboard' => 'Dashboard',
        'Dashboard' => 'Dashboard',
        'overview' => 'Übersicht',
        'Overview' => 'Übersicht',
        'statistics' => 'Statistiken',
        'Statistics' => 'Statistiken',
        'reports' => 'Berichte',
        'Reports' => 'Berichte',
        'settings' => 'Einstellungen',
        'Settings' => 'Einstellungen',
        'profile' => 'Profil',
        'Profile' => 'Profil',
        'notifications' => 'Benachrichtigungen',
        'Notifications' => 'Benachrichtigungen',
        'messages' => 'Nachrichten',
        'Messages' => 'Nachrichten',
        'activity' => 'Aktivität',
        'Activity' => 'Aktivität',
        'history' => 'Verlauf',
        'History' => 'Verlauf',
        'logs' => 'Protokolle',
        'Logs' => 'Protokolle',
        'widget' => 'Widget',
        'Widget' => 'Widget',
        'widgets' => 'Widgets',
        'Widgets' => 'Widgets',
        'chart' => 'Diagramm',
        'Chart' => 'Diagramm',
        'table' => 'Tabelle',
        'Table' => 'Tabelle',
        'list' => 'Liste',
        'List' => 'Liste',
        'grid' => 'Raster',
        'Grid' => 'Raster',
        'form' => 'Formular',
        'Form' => 'Formular',
    ];

    /**
     * Status-Begriffe
     */
    const STATUS_TERMS = [
        'active' => 'Aktiv',
        'Active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'Inactive' => 'Inaktiv',
        'pending' => 'Ausstehend',
        'Pending' => 'Ausstehend',
        'approved' => 'Genehmigt',
        'Approved' => 'Genehmigt',
        'rejected' => 'Abgelehnt',
        'Rejected' => 'Abgelehnt',
        'cancelled' => 'Storniert',
        'Cancelled' => 'Storniert',
        'scheduled' => 'Geplant',
        'Scheduled' => 'Geplant',
        'ongoing' => 'Laufend',
        'Ongoing' => 'Laufend',
        'finished' => 'Beendet',
        'Finished' => 'Beendet',
        'successful' => 'Erfolgreich',
        'Successful' => 'Erfolgreich',
        'unsuccessful' => 'Nicht erfolgreich',
        'Unsuccessful' => 'Nicht erfolgreich',
    ];

    /**
     * Zeit-Begriffe
     */
    const TIME_TERMS = [
        'today' => 'Heute',
        'Today' => 'Heute',
        'yesterday' => 'Gestern',
        'Yesterday' => 'Gestern',
        'tomorrow' => 'Morgen',
        'Tomorrow' => 'Morgen',
        'week' => 'Woche',
        'Week' => 'Woche',
        'month' => 'Monat',
        'Month' => 'Monat',
        'year' => 'Jahr',
        'Year' => 'Jahr',
        'date' => 'Datum',
        'Date' => 'Datum',
        'time' => 'Zeit',
        'Time' => 'Zeit',
        'hour' => 'Stunde',
        'Hour' => 'Stunde',
        'hours' => 'Stunden',
        'Hours' => 'Stunden',
        'minute' => 'Minute',
        'Minute' => 'Minute',
        'minutes' => 'Minuten',
        'Minutes' => 'Minuten',
        'second' => 'Sekunde',
        'Second' => 'Sekunde',
        'seconds' => 'Sekunden',
        'Seconds' => 'Sekunden',
    ];

    /**
     * Übersetze einen Begriff
     */
    public static function translate(string $term): string
    {
        // Durchsuche alle Term-Arrays
        $allTerms = array_merge(
            self::BUSINESS_TERMS,
            self::CALL_TERMS,
            self::ACTION_TERMS,
            self::UI_TERMS,
            self::STATUS_TERMS,
            self::TIME_TERMS
        );

        return $allTerms[$term] ?? $term;
    }

    /**
     * Übersetze mehrere Begriffe in einem Text
     */
    public static function translateText(string $text): string
    {
        $allTerms = array_merge(
            self::BUSINESS_TERMS,
            self::CALL_TERMS,
            self::ACTION_TERMS,
            self::UI_TERMS,
            self::STATUS_TERMS,
            self::TIME_TERMS
        );

        // Sortiere nach Länge (längere Begriffe zuerst)
        $terms = array_keys($allTerms);
        usort($terms, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        // Ersetze alle gefundenen Begriffe
        foreach ($terms as $english) {
            $german = $allTerms[$english];
            if ($english !== $german) {
                // Case-sensitive replacement
                $text = str_replace($english, $german, $text);
            }
        }

        return $text;
    }

    /**
     * Spezielle Übersetzung für Rollen
     */
    public static function translateRole(string $role): string
    {
        $roleTranslations = [
            'super_admin' => 'Super-Administrator',
            'reseller_admin' => 'Mandanten-Administrator',
            'reseller_owner' => 'Mandanten-Inhaber',
            'reseller_support' => 'Mandanten-Support',
            'company_admin' => 'Unternehmens-Administrator',
            'company_owner' => 'Unternehmens-Inhaber',
            'company_support' => 'Unternehmens-Support',
            'customer' => 'Kunde',
            'staff' => 'Mitarbeiter',
            'agent' => 'Agent',
        ];

        return $roleTranslations[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Übersetze Kosten-Label basierend auf Rolle
     */
    public static function getCostLabel(?object $user): string
    {
        if (!$user) {
            return 'Kosten';
        }

        if ($user->hasRole(['super_admin'])) {
            return 'Kosten (Alle Ebenen)';
        } elseif ($user->hasRole(['company_admin', 'company_owner', 'customer'])) {
            return 'Kosten (Kunde)';
        } elseif ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            return 'Kosten (Mandant)';
        }

        return 'Kosten';
    }

    /**
     * Übersetze Kostentyp
     */
    public static function translateCostType(string $type): string
    {
        $costTypes = [
            'base_cost' => 'Basiskosten',
            'reseller_cost' => 'Mandanten-Kosten',
            'customer_cost' => 'Kundenkosten',
            'cost' => 'Kosten',
            'total_cost' => 'Gesamtkosten',
            'markup' => 'Aufschlag',
            'discount' => 'Rabatt',
        ];

        return $costTypes[$type] ?? $type;
    }
}