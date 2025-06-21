<?php

return [
    'request_type' => [
        'export' => 'Datenexport',
        'deletion' => 'Datenlöschung',
        'rectification' => 'Datenberichtigung',
        'portability' => 'Datenportabilität',
    ],
    
    'request_status' => [
        'pending' => 'Ausstehend',
        'processing' => 'In Bearbeitung',
        'completed' => 'Abgeschlossen',
        'rejected' => 'Abgelehnt',
    ],
    
    'cookie_categories' => [
        'necessary' => 'Notwendige Cookies',
        'functional' => 'Funktionale Cookies',
        'analytics' => 'Analyse-Cookies',
        'marketing' => 'Marketing-Cookies',
    ],
    
    'messages' => [
        'consent_saved' => 'Ihre Cookie-Einstellungen wurden gespeichert.',
        'consent_withdrawn' => 'Ihre Cookie-Einwilligung wurde zurückgezogen.',
        'export_requested' => 'Ihre Datenanfrage wurde eingereicht. Sie erhalten eine E-Mail, sobald Ihre Daten bereit sind.',
        'deletion_requested' => 'Ihr Löschantrag wurde eingereicht. Ein Administrator wird Ihren Antrag prüfen.',
        'request_pending' => 'Sie haben bereits eine ausstehende Anfrage.',
        'download_ready' => 'Ihr Datenexport steht zum Download bereit.',
    ],
    
    'emails' => [
        'export_ready_subject' => 'Ihr Datenexport ist bereit',
        'export_ready_body' => 'Ihr angeforderter Datenexport wurde erstellt und steht zum Download bereit.',
        'deletion_confirmed_subject' => 'Ihre Daten wurden gelöscht',
        'deletion_confirmed_body' => 'Gemäß Ihrer Anfrage wurden Ihre persönlichen Daten aus unserem System gelöscht.',
    ],
];