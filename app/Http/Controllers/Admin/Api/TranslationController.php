<?php

namespace App\Http\Controllers\Admin\Api;

use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class TranslationController extends BaseAdminApiController
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Get translations for the admin panel
     */
    public function getTranslations(Request $request, string $locale = 'de'): JsonResponse
    {
        // Validate locale - support all 12 languages
        $supportedLocales = ['de', 'en', 'es', 'fr', 'it', 'tr', 'nl', 'pl', 'pt', 'ru', 'ja', 'zh'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'de';
        }

        // Cache key for translations
        $cacheKey = "admin_translations_{$locale}";
        
        // Try to get from cache first
        $translations = Cache::remember($cacheKey, now()->addDays(7), function () use ($locale) {
            return $this->loadTranslations($locale);
        });

        return response()->json([
            'locale' => $locale,
            'translations' => $translations
        ]);
    }

    /**
     * Translate text on demand
     */
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'target_lang' => 'required|string|size:2',
            'source_lang' => 'nullable|string|size:2'
        ]);

        $translated = $this->translationService->translate(
            $validated['text'],
            $validated['target_lang'],
            $validated['source_lang'] ?? null
        );

        return response()->json([
            'original' => $validated['text'],
            'translated' => $translated,
            'target_lang' => $validated['target_lang'],
            'source_lang' => $validated['source_lang'] ?? $this->translationService->getDetectedLanguage()
        ]);
    }

    /**
     * Get supported languages
     */
    public function languages(): JsonResponse
    {
        return response()->json([
            'languages' => $this->translationService->getSupportedLanguages(),
            'default' => 'de'
        ]);
    }

    /**
     * Load translations for a specific locale
     */
    protected function loadTranslations(string $locale): array
    {
        $translations = [
            'de' => [
                // Navigation
                'nav.dashboard' => 'Dashboard',
                'nav.calls' => 'Anrufe',
                'nav.appointments' => 'Termine',
                'nav.customers' => 'Kunden',
                'nav.billing' => 'Abrechnung',
                'nav.analytics' => 'Analysen',
                'nav.settings' => 'Einstellungen',
                'nav.team' => 'Team',
                
                // Common
                'common.search' => 'Suchen...',
                'common.filter' => 'Filter',
                'common.export' => 'Exportieren',
                'common.refresh' => 'Aktualisieren',
                'common.close' => 'Schließen',
                'common.save' => 'Speichern',
                'common.cancel' => 'Abbrechen',
                'common.delete' => 'Löschen',
                'common.edit' => 'Bearbeiten',
                'common.view' => 'Anzeigen',
                'common.details' => 'Details',
                'common.loading' => 'Lädt...',
                'common.no_data' => 'Keine Daten vorhanden',
                
                // Calls
                'calls.title' => 'Anrufe',
                'calls.live_calls' => 'Live-Anrufe',
                'calls.show_live' => 'Live-Anrufe anzeigen',
                'calls.hide_live' => 'Live-Ansicht beenden',
                'calls.active_calls' => 'Aktive Anrufe',
                'calls.no_active' => 'Keine aktiven Anrufe im Moment',
                'calls.duration' => 'Dauer',
                'calls.status' => 'Status',
                'calls.from' => 'Von',
                'calls.to' => 'An',
                'calls.cost' => 'Kosten',
                'calls.revenue' => 'Einnahmen',
                'calls.profit' => 'Profit',
                'calls.margin' => 'Marge',
                'calls.recording' => 'Aufzeichnung',
                'calls.transcript' => 'Transkript',
                'calls.summary' => 'Zusammenfassung',
                'calls.sentiment' => 'Stimmung',
                
                // Call Details
                'call_detail.overview' => 'Übersicht',
                'call_detail.costs_revenue' => 'Kosten & Einnahmen',
                'call_detail.transcript' => 'Transkript',
                'call_detail.basic_info' => 'Basis-Informationen',
                'call_detail.financial' => 'Finanziell',
                'call_detail.our_cost' => 'Unsere Kosten (Retell.ai)',
                'call_detail.customer_revenue' => 'Kundeneinnahmen',
                'call_detail.billing_rate' => 'Abrechnungssatz',
                'call_detail.billed_minutes' => 'Abgerechnete Minuten',
                'call_detail.play_recording' => 'Aufzeichnung abspielen',
                
                // Filters
                'filter.all' => 'Alle',
                'filter.today' => 'Heute',
                'filter.yesterday' => 'Gestern',
                'filter.last_7_days' => 'Letzte 7 Tage',
                'filter.last_30_days' => 'Letzte 30 Tage',
                'filter.completed' => 'Abgeschlossen',
                'filter.in_progress' => 'In Bearbeitung',
                'filter.failed' => 'Fehlgeschlagen',
                
                // Statistics
                'stats.total_calls' => 'Gesamtanrufe',
                'stats.calls_today' => 'Anrufe heute',
                'stats.answer_rate' => 'Annahmerate',
                'stats.avg_duration' => 'Ø Dauer',
                
                // Time units
                'time.seconds' => 'Sekunden',
                'time.minutes' => 'Minuten',
                'time.hours' => 'Stunden',
                'time.days' => 'Tage',
                
                // Sentiments
                'sentiment.positive' => 'Positiv',
                'sentiment.neutral' => 'Neutral',
                'sentiment.negative' => 'Negativ',
                
                // Actions
                'action.mark_non_billable' => 'Als nicht abrechenbar markieren',
                'action.create_refund' => 'Erstattung erstellen',
                'action.share' => 'Teilen',
                'action.download' => 'Herunterladen',
                'action.print' => 'Drucken',
                
                // Customer Form
                'customer.personal_info' => 'Persönliche Informationen',
                'customer.first_name' => 'Vorname',
                'customer.last_name' => 'Nachname',
                'customer.email' => 'E-Mail',
                'customer.phone' => 'Telefon',
                'customer.address' => 'Adresse',
                'customer.street' => 'Straße und Hausnummer',
                'customer.postal_code' => 'PLZ',
                'customer.city' => 'Stadt',
                'customer.company' => 'Unternehmen',
                'customer.branch' => 'Filiale',
                'customer.tags' => 'Tags',
                'customer.vip' => 'VIP Kunde',
                'customer.assignment' => 'Zuordnung',
                'customer.categorization' => 'Kategorisierung',
                'customer.communication' => 'Kommunikation',
                'customer.preferred_contact' => 'Bevorzugte Kontaktmethode',
                'customer.language' => 'Sprache',
                'customer.marketing_consent' => 'Marketing-Kommunikation erlaubt',
                'customer.portal_access' => 'Portal-Zugang',
                'customer.portal_enabled' => 'Portal-Zugang aktivieren',
                'customer.please_select' => 'Bitte wählen',
                
                // Status
                'status.active' => 'Aktiv',
                'status.inactive' => 'Inaktiv',
                'status.pending' => 'Ausstehend',
                'status.completed' => 'Abgeschlossen',
                'status.cancelled' => 'Abgebrochen',
                'status.failed' => 'Fehlgeschlagen',
                
                // Contact methods
                'contact.email' => 'E-Mail',
                'contact.phone' => 'Telefon',
                'contact.sms' => 'SMS',
                'contact.whatsapp' => 'WhatsApp',
                
                // Languages
                'language.de' => 'Deutsch',
                'language.en' => 'Englisch',
                'language.fr' => 'Französisch',
                'language.es' => 'Spanisch',
                'language.it' => 'Italienisch',
                'language.tr' => 'Türkisch',
                'language.nl' => 'Niederländisch',
                'language.pl' => 'Polnisch',
                'language.pt' => 'Portugiesisch',
                'language.ru' => 'Russisch',
                'language.ja' => 'Japanisch',
                'language.zh' => 'Chinesisch',
                
                // Customer Detail View
                'customer_since' => 'Kunde seit',
                'unknown_customer' => 'Unbekannter Kunde',
                'disable_portal' => 'Portal deaktivieren',
                'enable_portal' => 'Portal aktivieren',
                'book_appointment' => 'Termin buchen',
                'total_appointments' => 'Termine gesamt',
                'completed' => 'abgeschlossen',
                'total_calls' => 'Anrufe gesamt',
                'last_contact' => 'Letzter Kontakt',
                'no_shows' => 'Nicht erschienen',
                'warning_frequent_no_shows' => 'Achtung: Häufige No-Shows',
                'lifetime_value' => 'Gesamtumsatz',
                'overview' => 'Übersicht',
                'timeline' => 'Timeline',
                'appointments' => 'Termine',
                'notes' => 'Notizen',
                'documents' => 'Dokumente',
                'contact_information' => 'Kontaktinformationen',
                'address' => 'Adresse',
                'date_of_birth' => 'Geburtsdatum',
                'no_tags' => 'Keine Tags',
                'preferences' => 'Präferenzen',
                'preferred_language' => 'Bevorzugte Sprache',
                'contact_preference' => 'Kontaktpräferenz',
                'newsletter_subscribed' => 'Newsletter abonniert',
                'yes' => 'Ja',
                'no' => 'Nein',
                'activity_timeline' => 'Aktivitäts-Timeline',
                'add_note' => 'Notiz hinzufügen',
                'note_content' => 'Notizinhalt',
                'enter_note_content' => 'Notizinhalt eingeben',
                'category' => 'Kategorie',
                'select_category' => 'Kategorie wählen',
                'general' => 'Allgemein',
                'important' => 'Wichtig',
                'follow_up' => 'Nachfassen',
                'complaint' => 'Beschwerde',
                'mark_as_important' => 'Als wichtig markieren',
                'save_note' => 'Notiz speichern',
                'cancel' => 'Abbrechen',
                'appointments_list_coming_soon' => 'Terminliste kommt bald',
                'calls_list_coming_soon' => 'Anrufliste kommt bald',
                'notes_list_coming_soon' => 'Notizliste kommt bald',
                'documents_list_coming_soon' => 'Dokumentenliste kommt bald',
                'note_added_successfully' => 'Notiz erfolgreich hinzugefügt',
                'error_adding_note' => 'Fehler beim Hinzufügen der Notiz',
                'portal_enabled' => 'Portal aktiviert',
                'portal_disabled' => 'Portal deaktiviert',
                'error_updating_portal_access' => 'Fehler beim Aktualisieren des Portal-Zugangs',
                
                // Customer Appointments Tab
                'customer_appointments' => 'Kundentermine',
                'create_appointment' => 'Termin erstellen',
                'no_appointments' => 'Keine Termine vorhanden',
                'date_time' => 'Datum & Zeit',
                'service' => 'Leistung',
                'staff' => 'Mitarbeiter',
                'status' => 'Status',
                'actions' => 'Aktionen',
                'scheduled' => 'Geplant',
                'confirmed' => 'Bestätigt',
                'cancelled' => 'Storniert',
                'no_show' => 'Nicht erschienen',
                'confirm_cancel_appointment' => 'Möchten Sie diesen Termin wirklich stornieren?',
                'appointment_cancelled' => 'Termin wurde storniert',
                'error_cancelling_appointment' => 'Fehler beim Stornieren des Termins',
                
                // Customer Calls Tab
                'customer_calls' => 'Kundenanrufe',
                'no_calls' => 'Keine Anrufe vorhanden',
                'duration' => 'Dauer',
                'type' => 'Typ',
                'summary' => 'Zusammenfassung',
                'incoming' => 'Eingehend',
                'outgoing' => 'Ausgehend',
                
                // Customer Notes Tab
                'customer_notes' => 'Kundennotizen',
                'no_notes' => 'Keine Notizen vorhanden',
                'save' => 'Speichern',
                'confirm_delete_note' => 'Möchten Sie diese Notiz wirklich löschen?',
                'error_deleting_note' => 'Fehler beim Löschen der Notiz',
                'system' => 'System',
                
                // Customer Documents Tab
                'customer_documents' => 'Kundendokumente',
                'upload_document' => 'Dokument hochladen',
                'no_documents' => 'Keine Dokumente vorhanden'
            ],
            'en' => [
                // Navigation
                'nav.dashboard' => 'Dashboard',
                'nav.calls' => 'Calls',
                'nav.appointments' => 'Appointments',
                'nav.customers' => 'Customers',
                'nav.billing' => 'Billing',
                'nav.analytics' => 'Analytics',
                'nav.settings' => 'Settings',
                'nav.team' => 'Team',
                
                // Common
                'common.search' => 'Search...',
                'common.filter' => 'Filter',
                'common.export' => 'Export',
                'common.refresh' => 'Refresh',
                'common.close' => 'Close',
                'common.save' => 'Save',
                'common.cancel' => 'Cancel',
                'common.delete' => 'Delete',
                'common.edit' => 'Edit',
                'common.view' => 'View',
                'common.details' => 'Details',
                'common.loading' => 'Loading...',
                'common.no_data' => 'No data available',
                
                // Calls
                'calls.title' => 'Calls',
                'calls.live_calls' => 'Live Calls',
                'calls.show_live' => 'Show Live Calls',
                'calls.hide_live' => 'Hide Live View',
                'calls.active_calls' => 'Active Calls',
                'calls.no_active' => 'No active calls at the moment',
                'calls.duration' => 'Duration',
                'calls.status' => 'Status',
                'calls.from' => 'From',
                'calls.to' => 'To',
                'calls.cost' => 'Cost',
                'calls.revenue' => 'Revenue',
                'calls.profit' => 'Profit',
                'calls.margin' => 'Margin',
                'calls.recording' => 'Recording',
                'calls.transcript' => 'Transcript',
                'calls.summary' => 'Summary',
                'calls.sentiment' => 'Sentiment',
                
                // Call Details
                'call_detail.overview' => 'Overview',
                'call_detail.costs_revenue' => 'Costs & Revenue',
                'call_detail.transcript' => 'Transcript',
                'call_detail.basic_info' => 'Basic Information',
                'call_detail.financial' => 'Financial',
                'call_detail.our_cost' => 'Our Cost (Retell.ai)',
                'call_detail.customer_revenue' => 'Customer Revenue',
                'call_detail.billing_rate' => 'Billing Rate',
                'call_detail.billed_minutes' => 'Billed Minutes',
                'call_detail.play_recording' => 'Play Recording',
                
                // Filters
                'filter.all' => 'All',
                'filter.today' => 'Today',
                'filter.yesterday' => 'Yesterday',
                'filter.last_7_days' => 'Last 7 Days',
                'filter.last_30_days' => 'Last 30 Days',
                'filter.completed' => 'Completed',
                'filter.in_progress' => 'In Progress',
                'filter.failed' => 'Failed',
                
                // Statistics
                'stats.total_calls' => 'Total Calls',
                'stats.calls_today' => 'Calls Today',
                'stats.answer_rate' => 'Answer Rate',
                'stats.avg_duration' => 'Avg Duration',
                
                // Time units
                'time.seconds' => 'seconds',
                'time.minutes' => 'minutes',
                'time.hours' => 'hours',
                'time.days' => 'days',
                
                // Sentiments
                'sentiment.positive' => 'Positive',
                'sentiment.neutral' => 'Neutral',
                'sentiment.negative' => 'Negative',
                
                // Actions
                'action.mark_non_billable' => 'Mark as Non-Billable',
                'action.create_refund' => 'Create Refund',
                'action.share' => 'Share',
                'action.download' => 'Download',
                'action.print' => 'Print',
                
                // Customer Form
                'customer.personal_info' => 'Personal Information',
                'customer.first_name' => 'First Name',
                'customer.last_name' => 'Last Name',
                'customer.email' => 'Email',
                'customer.phone' => 'Phone',
                'customer.address' => 'Address',
                'customer.street' => 'Street and Number',
                'customer.postal_code' => 'Postal Code',
                'customer.city' => 'City',
                'customer.company' => 'Company',
                'customer.branch' => 'Branch',
                'customer.tags' => 'Tags',
                'customer.vip' => 'VIP Customer',
                'customer.assignment' => 'Assignment',
                'customer.categorization' => 'Categorization',
                'customer.communication' => 'Communication',
                'customer.preferred_contact' => 'Preferred Contact Method',
                'customer.language' => 'Language',
                'customer.marketing_consent' => 'Marketing Communication Allowed',
                'customer.portal_access' => 'Portal Access',
                'customer.portal_enabled' => 'Enable Portal Access',
                'customer.please_select' => 'Please Select',
                
                // Status
                'status.active' => 'Active',
                'status.inactive' => 'Inactive',
                'status.pending' => 'Pending',
                'status.completed' => 'Completed',
                'status.cancelled' => 'Cancelled',
                'status.failed' => 'Failed',
                
                // Contact methods
                'contact.email' => 'Email',
                'contact.phone' => 'Phone',
                'contact.sms' => 'SMS',
                'contact.whatsapp' => 'WhatsApp',
                
                // Languages
                'language.de' => 'German',
                'language.en' => 'English',
                'language.fr' => 'French',
                'language.es' => 'Spanish',
                'language.it' => 'Italian',
                'language.tr' => 'Turkish',
                'language.nl' => 'Dutch',
                'language.pl' => 'Polish',
                'language.pt' => 'Portuguese',
                'language.ru' => 'Russian',
                'language.ja' => 'Japanese',
                'language.zh' => 'Chinese'
            ],
            'es' => [
                // Navigation
                'nav.dashboard' => 'Panel de Control',
                'nav.calls' => 'Llamadas',
                'nav.appointments' => 'Citas',
                'nav.customers' => 'Clientes',
                'nav.billing' => 'Facturación',
                'nav.analytics' => 'Análisis',
                'nav.settings' => 'Configuración',
                'nav.team' => 'Equipo',
                
                // Common
                'common.search' => 'Buscar...',
                'common.filter' => 'Filtrar',
                'common.export' => 'Exportar',
                'common.refresh' => 'Actualizar',
                'common.close' => 'Cerrar',
                'common.save' => 'Guardar',
                'common.cancel' => 'Cancelar',
                'common.delete' => 'Eliminar',
                'common.edit' => 'Editar',
                'common.view' => 'Ver',
                'common.details' => 'Detalles',
                'common.loading' => 'Cargando...',
                'common.no_data' => 'Sin datos disponibles',
                
                // Calls
                'calls.title' => 'Llamadas',
                'calls.live_calls' => 'Llamadas en Vivo',
                'calls.show_live' => 'Mostrar Llamadas en Vivo',
                'calls.hide_live' => 'Ocultar Vista en Vivo',
                'calls.active_calls' => 'Llamadas Activas',
                'calls.no_active' => 'No hay llamadas activas en este momento',
                'calls.duration' => 'Duración',
                'calls.status' => 'Estado',
                'calls.from' => 'De',
                'calls.to' => 'Para',
                'calls.cost' => 'Costo',
                'calls.revenue' => 'Ingresos',
                'calls.profit' => 'Beneficio',
                'calls.margin' => 'Margen',
                
                // Call Details
                'call_detail.overview' => 'Resumen',
                'call_detail.costs_revenue' => 'Costos e Ingresos',
                'call_detail.transcript' => 'Transcripción',
                'call_detail.our_cost' => 'Nuestro Costo (Retell.ai)',
                'call_detail.customer_revenue' => 'Ingresos del Cliente',
                'call_detail.billing_rate' => 'Tarifa de Facturación',
                
                // Statistics
                'stats.total_calls' => 'Llamadas Totales',
                'stats.calls_today' => 'Llamadas Hoy',
                'stats.answer_rate' => 'Tasa de Respuesta',
                'stats.avg_duration' => 'Duración Promedio'
            ],
            'fr' => [
                // Navigation
                'nav.dashboard' => 'Tableau de Bord',
                'nav.calls' => 'Appels',
                'nav.appointments' => 'Rendez-vous',
                'nav.customers' => 'Clients',
                'nav.billing' => 'Facturation',
                'nav.analytics' => 'Analyses',
                'nav.settings' => 'Paramètres',
                'nav.team' => 'Équipe',
                
                // Common
                'common.search' => 'Rechercher...',
                'common.filter' => 'Filtrer',
                'common.export' => 'Exporter',
                'common.refresh' => 'Actualiser',
                'common.close' => 'Fermer',
                'common.save' => 'Enregistrer',
                'common.cancel' => 'Annuler',
                'common.delete' => 'Supprimer',
                'common.edit' => 'Modifier',
                'common.view' => 'Voir',
                'common.details' => 'Détails',
                'common.loading' => 'Chargement...',
                'common.no_data' => 'Aucune donnée disponible',
                
                // Calls
                'calls.title' => 'Appels',
                'calls.live_calls' => 'Appels en Direct',
                'calls.show_live' => 'Afficher les Appels en Direct',
                'calls.hide_live' => 'Masquer la Vue en Direct',
                'calls.active_calls' => 'Appels Actifs',
                'calls.no_active' => 'Aucun appel actif pour le moment',
                'calls.duration' => 'Durée',
                'calls.status' => 'Statut',
                'calls.from' => 'De',
                'calls.to' => 'À',
                'calls.cost' => 'Coût',
                'calls.revenue' => 'Revenus',
                'calls.profit' => 'Profit',
                'calls.margin' => 'Marge',
                
                // Call Details
                'call_detail.overview' => 'Aperçu',
                'call_detail.costs_revenue' => 'Coûts et Revenus',
                'call_detail.transcript' => 'Transcription',
                'call_detail.our_cost' => 'Notre Coût (Retell.ai)',
                'call_detail.customer_revenue' => 'Revenus Client',
                'call_detail.billing_rate' => 'Taux de Facturation',
                
                // Statistics
                'stats.total_calls' => 'Total des Appels',
                'stats.calls_today' => 'Appels Aujourd\'hui',
                'stats.answer_rate' => 'Taux de Réponse',
                'stats.avg_duration' => 'Durée Moyenne'
            ],
            'it' => [
                // Navigation
                'nav.dashboard' => 'Cruscotto',
                'nav.calls' => 'Chiamate',
                'nav.appointments' => 'Appuntamenti',
                'nav.customers' => 'Clienti',
                'nav.billing' => 'Fatturazione',
                'nav.analytics' => 'Analisi',
                'nav.settings' => 'Impostazioni',
                'nav.team' => 'Team',
                
                // Common
                'common.search' => 'Cerca...',
                'common.filter' => 'Filtra',
                'common.export' => 'Esporta',
                'common.refresh' => 'Aggiorna',
                'common.close' => 'Chiudi',
                'common.save' => 'Salva',
                'common.cancel' => 'Annulla',
                'common.delete' => 'Elimina',
                'common.edit' => 'Modifica',
                'common.view' => 'Visualizza',
                'common.details' => 'Dettagli',
                'common.loading' => 'Caricamento...',
                'common.no_data' => 'Nessun dato disponibile',
                
                // Calls
                'calls.title' => 'Chiamate',
                'calls.live_calls' => 'Chiamate in Diretta',
                'calls.show_live' => 'Mostra Chiamate in Diretta',
                'calls.hide_live' => 'Nascondi Vista in Diretta',
                'calls.active_calls' => 'Chiamate Attive',
                'calls.no_active' => 'Nessuna chiamata attiva al momento',
                'calls.duration' => 'Durata',
                'calls.status' => 'Stato',
                'calls.from' => 'Da',
                'calls.to' => 'A',
                'calls.cost' => 'Costo',
                'calls.revenue' => 'Ricavi',
                'calls.profit' => 'Profitto',
                'calls.margin' => 'Margine',
                
                // Call Details
                'call_detail.overview' => 'Panoramica',
                'call_detail.costs_revenue' => 'Costi e Ricavi',
                'call_detail.transcript' => 'Trascrizione',
                'call_detail.our_cost' => 'Nostro Costo (Retell.ai)',
                'call_detail.customer_revenue' => 'Ricavi Cliente',
                'call_detail.billing_rate' => 'Tariffa di Fatturazione',
                
                // Statistics
                'stats.total_calls' => 'Chiamate Totali',
                'stats.calls_today' => 'Chiamate Oggi',
                'stats.answer_rate' => 'Tasso di Risposta',
                'stats.avg_duration' => 'Durata Media'
            ],
            'tr' => [
                // Navigation
                'nav.dashboard' => 'Kontrol Paneli',
                'nav.calls' => 'Aramalar',
                'nav.appointments' => 'Randevular',
                'nav.customers' => 'Müşteriler',
                'nav.billing' => 'Faturalandırma',
                'nav.analytics' => 'Analizler',
                'nav.settings' => 'Ayarlar',
                'nav.team' => 'Ekip',
                
                // Common
                'common.search' => 'Ara...',
                'common.filter' => 'Filtrele',
                'common.export' => 'Dışa Aktar',
                'common.refresh' => 'Yenile',
                'common.close' => 'Kapat',
                'common.save' => 'Kaydet',
                'common.cancel' => 'İptal',
                'common.delete' => 'Sil',
                'common.edit' => 'Düzenle',
                'common.view' => 'Görüntüle',
                'common.details' => 'Detaylar',
                'common.loading' => 'Yükleniyor...',
                'common.no_data' => 'Veri bulunamadı',
                
                // Calls
                'calls.title' => 'Aramalar',
                'calls.live_calls' => 'Canlı Aramalar',
                'calls.show_live' => 'Canlı Aramaları Göster',
                'calls.hide_live' => 'Canlı Görünümü Gizle',
                'calls.active_calls' => 'Aktif Aramalar',
                'calls.no_active' => 'Şu anda aktif arama yok',
                'calls.duration' => 'Süre',
                'calls.status' => 'Durum',
                'calls.from' => 'Arayan',
                'calls.to' => 'Aranan',
                'calls.cost' => 'Maliyet',
                'calls.revenue' => 'Gelir',
                'calls.profit' => 'Kâr',
                'calls.margin' => 'Marj',
                
                // Call Details
                'call_detail.overview' => 'Genel Bakış',
                'call_detail.costs_revenue' => 'Maliyet ve Gelir',
                'call_detail.transcript' => 'Transkript',
                'call_detail.our_cost' => 'Bizim Maliyetimiz (Retell.ai)',
                'call_detail.customer_revenue' => 'Müşteri Geliri',
                'call_detail.billing_rate' => 'Faturalandırma Oranı',
                
                // Statistics
                'stats.total_calls' => 'Toplam Arama',
                'stats.calls_today' => 'Bugünkü Aramalar',
                'stats.answer_rate' => 'Cevaplama Oranı',
                'stats.avg_duration' => 'Ortalama Süre'
            ],
            'nl' => [
                // Navigation
                'nav.dashboard' => 'Dashboard',
                'nav.calls' => 'Oproepen',
                'nav.appointments' => 'Afspraken',
                'nav.customers' => 'Klanten',
                'nav.billing' => 'Facturering',
                'nav.analytics' => 'Analyses',
                'nav.settings' => 'Instellingen',
                'nav.team' => 'Team',
                
                // Common
                'common.search' => 'Zoeken...',
                'common.filter' => 'Filteren',
                'common.export' => 'Exporteren',
                'common.refresh' => 'Vernieuwen',
                'common.close' => 'Sluiten',
                'common.save' => 'Opslaan',
                'common.cancel' => 'Annuleren',
                'common.delete' => 'Verwijderen',
                'common.edit' => 'Bewerken',
                'common.view' => 'Bekijken',
                'common.details' => 'Details',
                'common.loading' => 'Laden...',
                'common.no_data' => 'Geen gegevens beschikbaar',
                
                // Calls
                'calls.title' => 'Oproepen',
                'calls.live_calls' => 'Live Oproepen',
                'calls.show_live' => 'Toon Live Oproepen',
                'calls.hide_live' => 'Verberg Live Weergave',
                'calls.active_calls' => 'Actieve Oproepen',
                'calls.no_active' => 'Momenteel geen actieve oproepen',
                'calls.duration' => 'Duur',
                'calls.status' => 'Status',
                'calls.from' => 'Van',
                'calls.to' => 'Naar',
                'calls.cost' => 'Kosten',
                'calls.revenue' => 'Omzet',
                'calls.profit' => 'Winst',
                'calls.margin' => 'Marge',
                
                // Call Details
                'call_detail.overview' => 'Overzicht',
                'call_detail.costs_revenue' => 'Kosten & Omzet',
                'call_detail.transcript' => 'Transcriptie',
                'call_detail.our_cost' => 'Onze Kosten (Retell.ai)',
                'call_detail.customer_revenue' => 'Klantomzet',
                'call_detail.billing_rate' => 'Facturatietarief',
                
                // Statistics
                'stats.total_calls' => 'Totaal Oproepen',
                'stats.calls_today' => 'Oproepen Vandaag',
                'stats.answer_rate' => 'Antwoordpercentage',
                'stats.avg_duration' => 'Gem. Duur'
            ],
            'pl' => [
                // Navigation
                'nav.dashboard' => 'Panel Kontrolny',
                'nav.calls' => 'Połączenia',
                'nav.appointments' => 'Spotkania',
                'nav.customers' => 'Klienci',
                'nav.billing' => 'Rozliczenia',
                'nav.analytics' => 'Analizy',
                'nav.settings' => 'Ustawienia',
                'nav.team' => 'Zespół',
                
                // Common
                'common.search' => 'Szukaj...',
                'common.filter' => 'Filtruj',
                'common.export' => 'Eksportuj',
                'common.refresh' => 'Odśwież',
                'common.close' => 'Zamknij',
                'common.save' => 'Zapisz',
                'common.cancel' => 'Anuluj',
                'common.delete' => 'Usuń',
                'common.edit' => 'Edytuj',
                'common.view' => 'Zobacz',
                'common.details' => 'Szczegóły',
                'common.loading' => 'Ładowanie...',
                'common.no_data' => 'Brak danych',
                
                // Calls
                'calls.title' => 'Połączenia',
                'calls.live_calls' => 'Połączenia na Żywo',
                'calls.show_live' => 'Pokaż Połączenia na Żywo',
                'calls.hide_live' => 'Ukryj Widok na Żywo',
                'calls.active_calls' => 'Aktywne Połączenia',
                'calls.no_active' => 'Brak aktywnych połączeń',
                'calls.duration' => 'Czas Trwania',
                'calls.status' => 'Status',
                'calls.from' => 'Od',
                'calls.to' => 'Do',
                'calls.cost' => 'Koszt',
                'calls.revenue' => 'Przychód',
                'calls.profit' => 'Zysk',
                'calls.margin' => 'Marża',
                
                // Call Details
                'call_detail.overview' => 'Przegląd',
                'call_detail.costs_revenue' => 'Koszty i Przychody',
                'call_detail.transcript' => 'Transkrypcja',
                'call_detail.our_cost' => 'Nasz Koszt (Retell.ai)',
                'call_detail.customer_revenue' => 'Przychód od Klienta',
                'call_detail.billing_rate' => 'Stawka Rozliczeniowa',
                
                // Statistics
                'stats.total_calls' => 'Łącznie Połączeń',
                'stats.calls_today' => 'Połączenia Dziś',
                'stats.answer_rate' => 'Współczynnik Odpowiedzi',
                'stats.avg_duration' => 'Średni Czas'
            ],
            'pt' => [
                // Navigation
                'nav.dashboard' => 'Painel',
                'nav.calls' => 'Chamadas',
                'nav.appointments' => 'Compromissos',
                'nav.customers' => 'Clientes',
                'nav.billing' => 'Faturamento',
                'nav.analytics' => 'Análises',
                'nav.settings' => 'Configurações',
                'nav.team' => 'Equipe',
                
                // Common
                'common.search' => 'Pesquisar...',
                'common.filter' => 'Filtrar',
                'common.export' => 'Exportar',
                'common.refresh' => 'Atualizar',
                'common.close' => 'Fechar',
                'common.save' => 'Salvar',
                'common.cancel' => 'Cancelar',
                'common.delete' => 'Excluir',
                'common.edit' => 'Editar',
                'common.view' => 'Visualizar',
                'common.details' => 'Detalhes',
                'common.loading' => 'Carregando...',
                'common.no_data' => 'Sem dados disponíveis',
                
                // Calls
                'calls.title' => 'Chamadas',
                'calls.live_calls' => 'Chamadas ao Vivo',
                'calls.show_live' => 'Mostrar Chamadas ao Vivo',
                'calls.hide_live' => 'Ocultar Visualização ao Vivo',
                'calls.active_calls' => 'Chamadas Ativas',
                'calls.no_active' => 'Nenhuma chamada ativa no momento',
                'calls.duration' => 'Duração',
                'calls.status' => 'Status',
                'calls.from' => 'De',
                'calls.to' => 'Para',
                'calls.cost' => 'Custo',
                'calls.revenue' => 'Receita',
                'calls.profit' => 'Lucro',
                'calls.margin' => 'Margem',
                
                // Call Details
                'call_detail.overview' => 'Visão Geral',
                'call_detail.costs_revenue' => 'Custos e Receitas',
                'call_detail.transcript' => 'Transcrição',
                'call_detail.our_cost' => 'Nosso Custo (Retell.ai)',
                'call_detail.customer_revenue' => 'Receita do Cliente',
                'call_detail.billing_rate' => 'Taxa de Cobrança',
                
                // Statistics
                'stats.total_calls' => 'Total de Chamadas',
                'stats.calls_today' => 'Chamadas Hoje',
                'stats.answer_rate' => 'Taxa de Resposta',
                'stats.avg_duration' => 'Duração Média'
            ],
            'ru' => [
                // Navigation
                'nav.dashboard' => 'Панель управления',
                'nav.calls' => 'Звонки',
                'nav.appointments' => 'Встречи',
                'nav.customers' => 'Клиенты',
                'nav.billing' => 'Биллинг',
                'nav.analytics' => 'Аналитика',
                'nav.settings' => 'Настройки',
                'nav.team' => 'Команда',
                
                // Common
                'common.search' => 'Поиск...',
                'common.filter' => 'Фильтр',
                'common.export' => 'Экспорт',
                'common.refresh' => 'Обновить',
                'common.close' => 'Закрыть',
                'common.save' => 'Сохранить',
                'common.cancel' => 'Отмена',
                'common.delete' => 'Удалить',
                'common.edit' => 'Редактировать',
                'common.view' => 'Просмотр',
                'common.details' => 'Детали',
                'common.loading' => 'Загрузка...',
                'common.no_data' => 'Данные отсутствуют',
                
                // Calls
                'calls.title' => 'Звонки',
                'calls.live_calls' => 'Активные звонки',
                'calls.show_live' => 'Показать активные звонки',
                'calls.hide_live' => 'Скрыть активные звонки',
                'calls.active_calls' => 'Активные звонки',
                'calls.no_active' => 'Нет активных звонков',
                'calls.duration' => 'Длительность',
                'calls.status' => 'Статус',
                'calls.from' => 'От',
                'calls.to' => 'Кому',
                'calls.cost' => 'Стоимость',
                'calls.revenue' => 'Доход',
                'calls.profit' => 'Прибыль',
                'calls.margin' => 'Маржа',
                
                // Call Details
                'call_detail.overview' => 'Обзор',
                'call_detail.costs_revenue' => 'Расходы и доходы',
                'call_detail.transcript' => 'Транскрипция',
                'call_detail.our_cost' => 'Наши затраты (Retell.ai)',
                'call_detail.customer_revenue' => 'Доход от клиента',
                'call_detail.billing_rate' => 'Тариф',
                
                // Statistics
                'stats.total_calls' => 'Всего звонков',
                'stats.calls_today' => 'Звонков сегодня',
                'stats.answer_rate' => 'Процент ответов',
                'stats.avg_duration' => 'Средняя длительность'
            ],
            'ja' => [
                // Navigation
                'nav.dashboard' => 'ダッシュボード',
                'nav.calls' => '通話',
                'nav.appointments' => '予約',
                'nav.customers' => '顧客',
                'nav.billing' => '請求',
                'nav.analytics' => '分析',
                'nav.settings' => '設定',
                'nav.team' => 'チーム',
                
                // Common
                'common.search' => '検索...',
                'common.filter' => 'フィルター',
                'common.export' => 'エクスポート',
                'common.refresh' => '更新',
                'common.close' => '閉じる',
                'common.save' => '保存',
                'common.cancel' => 'キャンセル',
                'common.delete' => '削除',
                'common.edit' => '編集',
                'common.view' => '表示',
                'common.details' => '詳細',
                'common.loading' => '読み込み中...',
                'common.no_data' => 'データがありません',
                
                // Calls
                'calls.title' => '通話',
                'calls.live_calls' => 'ライブ通話',
                'calls.show_live' => 'ライブ通話を表示',
                'calls.hide_live' => 'ライブビューを非表示',
                'calls.active_calls' => 'アクティブな通話',
                'calls.no_active' => '現在アクティブな通話はありません',
                'calls.duration' => '通話時間',
                'calls.status' => 'ステータス',
                'calls.from' => '発信元',
                'calls.to' => '宛先',
                'calls.cost' => 'コスト',
                'calls.revenue' => '収益',
                'calls.profit' => '利益',
                'calls.margin' => 'マージン',
                
                // Call Details
                'call_detail.overview' => '概要',
                'call_detail.costs_revenue' => 'コストと収益',
                'call_detail.transcript' => 'トランスクリプト',
                'call_detail.our_cost' => '当社のコスト (Retell.ai)',
                'call_detail.customer_revenue' => '顧客収益',
                'call_detail.billing_rate' => '請求レート',
                
                // Statistics
                'stats.total_calls' => '総通話数',
                'stats.calls_today' => '今日の通話',
                'stats.answer_rate' => '応答率',
                'stats.avg_duration' => '平均通話時間'
            ],
            'zh' => [
                // Navigation
                'nav.dashboard' => '仪表板',
                'nav.calls' => '通话记录',
                'nav.appointments' => '预约',
                'nav.customers' => '客户',
                'nav.billing' => '账单',
                'nav.analytics' => '分析',
                'nav.settings' => '设置',
                'nav.team' => '团队',
                
                // Common
                'common.search' => '搜索...',
                'common.filter' => '筛选',
                'common.export' => '导出',
                'common.refresh' => '刷新',
                'common.close' => '关闭',
                'common.save' => '保存',
                'common.cancel' => '取消',
                'common.delete' => '删除',
                'common.edit' => '编辑',
                'common.view' => '查看',
                'common.details' => '详情',
                'common.loading' => '加载中...',
                'common.no_data' => '暂无数据',
                
                // Calls
                'calls.title' => '通话记录',
                'calls.live_calls' => '实时通话',
                'calls.show_live' => '显示实时通话',
                'calls.hide_live' => '隐藏实时视图',
                'calls.active_calls' => '活跃通话',
                'calls.no_active' => '当前没有活跃通话',
                'calls.duration' => '时长',
                'calls.status' => '状态',
                'calls.from' => '来自',
                'calls.to' => '致电',
                'calls.cost' => '成本',
                'calls.revenue' => '收入',
                'calls.profit' => '利润',
                'calls.margin' => '利润率',
                
                // Call Details
                'call_detail.overview' => '概览',
                'call_detail.costs_revenue' => '成本与收入',
                'call_detail.transcript' => '通话记录',
                'call_detail.our_cost' => '我们的成本 (Retell.ai)',
                'call_detail.customer_revenue' => '客户收入',
                'call_detail.billing_rate' => '计费费率',
                
                // Statistics
                'stats.total_calls' => '总通话数',
                'stats.calls_today' => '今日通话',
                'stats.answer_rate' => '接听率',
                'stats.avg_duration' => '平均时长'
            ]
        ];

        // Return translations for requested locale, fallback to German
        return $translations[$locale] ?? $translations['de'];
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): JsonResponse
    {
        $locales = ['de', 'en', 'es', 'fr', 'it', 'tr', 'nl', 'pl', 'pt', 'ru', 'ja', 'zh'];
        
        foreach ($locales as $locale) {
            Cache::forget("admin_translations_{$locale}");
        }

        return response()->json([
            'message' => 'Translation cache cleared successfully'
        ]);
    }
}