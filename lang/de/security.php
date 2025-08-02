<?php

return [
    // Rate Limiting - Freundliche Messages
    'rate_limit' => [
        'title' => 'Kurze Pause benötigt!',
        'message' => 'Du warst gerade sehr aktiv! 🚀 Wir brauchen nur eine kurze Verschnaufpause.',
        'message_friendly' => 'Hey, du bist ja richtig fleißig heute! 😊 Lass uns kurz durchatmen und dann geht\'s weiter.',
        'countdown' => 'Weiter in :seconds Sekunden',
        'retry_in' => 'Du kannst es in :time wieder versuchen',
        'tip' => '💡 Tipp: Nutze die Zeit für einen Kaffee oder schau dir die Hilfe-Sektion an!',
        'reasons' => [
            'high_activity' => 'Du warst in letzter Zeit sehr aktiv',
            'many_requests' => 'Viele Anfragen in kurzer Zeit',
            'security_protection' => 'Das schützt alle vor Überlastung',
        ],
        'actions' => [
            'wait' => 'Entspannt warten',
            'learn_more' => 'Mehr über Rate Limits erfahren',
            'contact_support' => 'Support kontaktieren',
        ],
    ],

    // 2FA Setup - Gamification & Motivation
    'two_factor' => [
        'setup' => [
            'title' => '🛡️ Dein Sicherheits-Upgrade!',
            'subtitle' => 'Mach dein Konto unbezwingbar',
            'intro' => 'Mit der Zwei-Faktor-Authentifizierung wird dein Konto zu einer digitalen Festung! 🏰',
            'benefits' => [
                'super_secure' => '🔒 Doppelte Sicherheit',
                'peace_of_mind' => '😌 Beruhigendes Gefühl',
                'industry_standard' => '⭐ Professioneller Standard',
                'quick_setup' => '⚡ Nur 2 Minuten Setup',
            ],
            'steps' => [
                'scan_qr' => [
                    'title' => 'QR-Code scannen',
                    'description' => 'Öffne deine Authenticator-App und scanne den Code',
                    'emoji' => '📱',
                ],
                'enter_code' => [
                    'title' => 'Code eingeben',
                    'description' => 'Gib den 6-stelligen Code aus der App ein',
                    'emoji' => '🔢',
                ],
                'celebrate' => [
                    'title' => 'Fertig!',
                    'description' => 'Du bist jetzt super sicher geschützt!',
                    'emoji' => '🎉',
                ],
            ],
        ],
        'required' => [
            'title' => 'Sicherheits-Check erforderlich',
            'message' => 'Dein Administrator möchte, dass alle Accounts extra sicher sind. Das dauert nur 2 Minuten! 🚀',
            'motivation' => 'Du machst das großartig! Gleich hast du ein super sicheres Konto.',
        ],
        'verify' => [
            'title' => 'Sicherheitscode eingeben',
            'subtitle' => 'Schau in deine Authenticator-App',
            'placeholder' => '000000',
            'help' => 'Code nicht gefunden? Schau in die App auf deinem Handy 📱',
        ],
    ],

    // Session Timeouts - Proaktive Warnungen
    'session' => [
        'warning' => [
            'title' => 'Session läuft bald ab',
            'message' => 'Du wirst in :minutes Minuten automatisch abgemeldet.',
            'motivation' => 'Kein Problem! Ein Klick genügt, um weiter zu arbeiten.',
            'actions' => [
                'extend' => 'Session verlängern',
                'save_work' => 'Arbeit speichern',
                'logout' => 'Jetzt abmelden',
            ],
        ],
        'expired' => [
            'title' => 'Session abgelaufen',
            'message' => 'Aus Sicherheitsgründen wurdest du abgemeldet.',
            'motivation' => 'Das ist ganz normal! Melde dich einfach wieder an.',
            'tip' => '💡 Tipp: Deine Arbeit wurde automatisch gespeichert.',
        ],
        'extended' => [
            'title' => 'Session verlängert!',
            'message' => 'Du kannst weiter arbeiten. 👍',
            'duration' => 'Aktiv für weitere :hours Stunden',
        ],
    ],

    // Permission Errors - Hilfreiche Erklärungen
    'permissions' => [
        'denied' => [
            'title' => 'Diese Funktion ist gerade nicht verfügbar',
            'message' => 'Du hast momentan keine Berechtigung für diese Aktion.',
            'reasons' => [
                'role_restriction' => 'Deine Rolle erlaubt diese Aktion nicht',
                'feature_locked' => 'Diese Funktion ist für dein Paket nicht freigeschaltet',
                'temporary_restriction' => 'Vorübergehende Einschränkung',
            ],
            'actions' => [
                'contact_admin' => 'Administrator kontaktieren',
                'upgrade_plan' => 'Plan upgraden',
                'learn_more' => 'Mehr über Berechtigungen',
                'go_back' => 'Zurück gehen',
            ],
            'tips' => [
                'check_role' => '💡 Prüfe deine Rolle in den Einstellungen',
                'ask_admin' => '👥 Frage deinen Administrator',
                'read_docs' => '📖 Schau in die Hilfe-Sektion',
            ],
        ],
        'insufficient' => [
            'title' => 'Fast geschafft!',
            'message' => 'Du brauchst noch ein paar zusätzliche Berechtigungen.',
            'motivation' => 'Kein Problem - das ist schnell geklärt!',
        ],
    ],

    // Tenant/Company Switching
    'tenant_switching' => [
        'success' => [
            'title' => 'Erfolgreich gewechselt!',
            'message' => 'Du arbeitest jetzt als :company_name',
            'welcome' => 'Willkommen bei :company_name! 🎉',
        ],
        'loading' => [
            'title' => 'Wechsle Arbeitsbereich...',
            'message' => 'Bereite deine neue Umgebung vor',
            'steps' => [
                'authenticating' => 'Authentifizierung...',
                'loading_data' => 'Daten laden...',
                'preparing_ui' => 'Oberfläche vorbereiten...',
                'ready' => 'Bereit!',
            ],
        ],
        'error' => [
            'title' => 'Wechsel nicht möglich',
            'message' => 'Der Wechsel zu :company_name ist momentan nicht möglich.',
            'reasons' => [
                'no_access' => 'Du hast keine Berechtigung für diese Firma',
                'maintenance' => 'Diese Firma ist gerade in Wartung',
                'suspended' => 'Dieser Account ist vorübergehend gesperrt',
            ],
            'actions' => [
                'try_again' => 'Erneut versuchen',
                'contact_support' => 'Support kontaktieren',
                'stay_here' => 'Hier bleiben',
            ],
        ],
    ],

    // Security Score & Gamification
    'security_score' => [
        'title' => 'Dein Sicherheits-Score',
        'levels' => [
            'basic' => [
                'name' => 'Sicherheits-Anfänger',
                'description' => 'Du hast die Grundlagen drauf!',
                'emoji' => '🛡️',
                'color' => 'orange',
            ],
            'good' => [
                'name' => 'Sicherheits-Profi',
                'description' => 'Sehr gut! Du kennst dich aus.',
                'emoji' => '🔒',
                'color' => 'blue',
            ],
            'excellent' => [
                'name' => 'Sicherheits-Experte',
                'description' => 'Wow! Du bist ein Sicherheits-Champion!',
                'emoji' => '🏆',
                'color' => 'green',
            ],
            'master' => [
                'name' => 'Sicherheits-Meister',
                'description' => 'Legendär! Du bist die Benchmark!',
                'emoji' => '👑',
                'color' => 'gold',
            ],
        ],
        'improvements' => [
            'enable_2fa' => '+30 Punkte: Zwei-Faktor-Auth aktivieren',
            'strong_password' => '+20 Punkte: Starkes Passwort verwenden',
            'regular_activity' => '+10 Punkte: Regelmäßige Aktivität',
            'security_questions' => '+15 Punkte: Sicherheitsfragen einrichten',
        ],
    ],

    // Proactive Security Tips
    'tips' => [
        'daily' => [
            'Melde dich immer ab, wenn du fertig bist 👋',
            'Teile niemals dein Passwort - auch nicht mit Kollegen 🤐',
            'Prüfe regelmäßig deine Login-Aktivitäten 🔍',
            'Halte deine Authenticator-App aktuell 📱',
        ],
        'weekly' => [
            'Überprüfe deine Sicherheitseinstellungen wöchentlich 🗓️',
            'Schau dir neue Security-Features an 🆕',
            'Teste deine 2FA-Backup-Codes 🔑',
        ],
        'random' => [
            'Wusstest du? 2FA reduziert das Hack-Risiko um 99.9%! 📊',
            'Pro-Tipp: Nutze einen Passwort-Manager 🗂️',
            'Sicherheit ist ein Teamwork - danke, dass du mitmachst! 🤝',
            'Jeder sichere Login macht das ganze System stärker 💪',
        ],
    ],

    // Error Recovery
    'recovery' => [
        'locked_out' => [
            'title' => 'Account vorübergehend gesperrt',
            'message' => 'Keine Sorge! Das passiert manchmal aus Sicherheitsgründen.',
            'help' => 'Das ist ganz normal und schützt deinen Account vor unbefugtem Zugriff.',
            'wait_time' => 'Automatische Entsperrung in :minutes Minuten',
            'actions' => [
                'wait' => 'Geduldig warten',
                'contact_admin' => 'Administrator kontaktieren',
                'reset_password' => 'Passwort zurücksetzen',
            ],
        ],
        'too_many_attempts' => [
            'title' => 'Zu viele Versuche',
            'message' => 'Du hast es wirklich versucht! 😅 Lass uns eine kurze Pause machen.',
            'motivation' => 'Das schützt deinen Account - du machst nichts falsch!',
            'next_steps' => [
                'Prüfe dein Passwort noch einmal',
                'Warte :minutes Minuten',
                'Versuche es dann erneut',
            ],
        ],
    ],

    // Success Messages
    'success' => [
        'login' => [
            'welcome_back' => 'Willkommen zurück! 🎉',
            'secure_login' => 'Sicherer Login erfolgreich ✅',
            'all_systems_go' => 'Alle Systeme bereit! 🚀',
        ],
        '2fa_enabled' => [
            'title' => 'Zwei-Faktor-Auth aktiviert!',
            'message' => 'Dein Account ist jetzt super sicher! 🛡️',
            'celebration' => 'Du bist jetzt ein Sicherheits-Champion! 🏆',
        ],
        'password_changed' => [
            'title' => 'Passwort aktualisiert!',
            'message' => 'Dein neues Passwort ist aktiv 🔑',
            'tip' => 'Vergiss nicht, es auch in deinem Passwort-Manager zu aktualisieren!',
        ],
    ],

    // Loading States
    'loading' => [
        'authenticating' => 'Authentifizierung läuft...',
        'verifying' => 'Verifizierung läuft...',
        'securing' => 'Sicherheitscheck läuft...',
        'preparing' => 'Bereite vor...',
        'almost_done' => 'Gleich geschafft...',
    ],

    // Help & Guidance
    'help' => [
        'contact_support' => 'Brauchst du Hilfe? Kontaktiere unseren Support!',
        'documentation' => 'Mehr in der Dokumentation erfahren',
        'video_tutorial' => 'Video-Tutorial ansehen',
        'faq' => 'Häufige Fragen (FAQ)',
    ],
];