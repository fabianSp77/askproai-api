# 📋 Empfohlene Menüstruktur für das Admin-Portal

## 🎯 Ziele der Neustrukturierung
- Reduzierung von 16 auf 8 Navigation Groups
- Konsistente deutsche Bezeichnungen
- Klare Priorisierung nach Nutzungshäufigkeit
- Verstecken von Test-/Debug-Funktionen für normale Benutzer

## 📊 Neue Menüstruktur

### 1. **Täglicher Betrieb** (Priorität: HOCH)
*Für die tägliche Arbeit mit Kunden und Terminen*

**Resources:**
- 📞 **Anrufe** (CallResource) - Sort: 1
- 📅 **Termine** (AppointmentResource) - Sort: 2
- 📊 **Dashboard** (Dashboard.php) - Sort: 3

**Warum hier:**
- Meistgenutzte Funktionen
- Operative Tätigkeiten
- Direkter Kundenkontakt

### 2. **Kundenverwaltung**
*Alles rund um Kundenbeziehungen*

**Resources:**
- 👥 **Kunden** (CustomerResource) - Sort: 1
- 📧 **Kundenportal** (CustomerPortalManagement.php) - Sort: 2
- 📚 **Wissensdatenbank** (KnowledgeBaseManager.php) - Sort: 3

### 3. **Unternehmensstruktur**
*Verwaltung der Firmenorganisation*

**Resources:**
- 🏢 **Firmen** (CompanyResource) - Sort: 1
- 🏪 **Filialen** (BranchResource) - Sort: 2
- 👨‍💼 **Mitarbeiter** (StaffResource) - Sort: 3
- 🛎️ **Dienstleistungen** (ServiceResource) - Sort: 4
- ⏰ **Arbeitszeiten** (WorkingHourResource) - Sort: 5
- 📞 **Telefonnummern** (PhoneNumberResource) - Sort: 6

### 4. **Integrationen**
*Externe Systeme und APIs*

**Resources:**
- 🔌 **Integrationen** (IntegrationResource) - Sort: 1
- 📅 **Cal.com Event Types** (CalcomEventTypeResource) - Sort: 2
- 🤖 **Retell Agenten** (RetellAgentResource) - Sort: 3

**Pages:**
- 🔧 **Integration Hub** (IntegrationHub.php) - Sort: 4
- 📅 **Cal.com Sync Status** (CalcomSyncStatus.php) - Sort: 5
- 🎯 **Retell Configuration** (RetellConfigurationCenter.php) - Sort: 6

### 5. **Finanzen & Abrechnung**
*Rechnungen und Zahlungen*

**Resources:**
- 💰 **Rechnungen** (InvoiceResource) - Sort: 1
- 📊 **Abrechnungszeiträume** (BillingPeriodResource) - Sort: 2
- 💳 **Prepaid-Guthaben** (PrepaidBalanceResource) - Sort: 3
- 📋 **Abonnements** (SubscriptionResource) - Sort: 4
- 💲 **Firmenpreise** (CompanyPricingResource) - Sort: 5

**Pages:**
- 💳 **Stripe Payment Links** (StripePaymentLinks.php) - Sort: 6
- 💰 **Preisrechner** (PricingCalculator.php) - Sort: 7

### 6. **Einstellungen**
*Konfiguration und Personalisierung*

**Wichtigste Pages (sichtbar für alle):**
- 🏢 **Firmenkonfiguration** (BasicCompanyConfig.php) - Sort: 1
- 🔔 **Benachrichtigungen** (NotificationSettings.php) - Sort: 2
- 🌍 **Sprache** (LanguageSettings.php) - Sort: 3
- 🔐 **Zwei-Faktor-Auth** (TwoFactorAuthentication.php) - Sort: 4

**Admin-only Pages:**
- 🏪 **Business Portal Admin** (BusinessPortalAdmin.php) - Sort: 10
- 🚩 **Feature Flags** (FeatureFlagManager.php) - Sort: 11

### 7. **System & Monitoring** (Nur für Admins)
*Systemüberwachung und -verwaltung*

**Pages:**
- 📊 **System Monitor** (SystemMonitoringDashboard.php) - Sort: 1
- 📈 **API Health** (ApiHealthMonitor.php) - Sort: 2
- ⚡ **Circuit Breaker** (CircuitBreakerMonitor.php) - Sort: 3
- 🔍 **Webhook Monitor** (WebhookMonitor.php) - Sort: 4
- 🔄 **Sync Manager** (IntelligentSyncManager.php) - Sort: 5

### 8. **Entwicklung** (Nur für Super-Admins)
*Test- und Debug-Funktionen*

**Pages:**
- 🐛 **System Debug** (SystemDebug.php)
- 🧪 **Test Dashboard** (TestMinimalDashboard.php)
- 🧪 **Widget Tests** (WidgetTestPage.php)
- Alle anderen Test-Pages

## 🔧 Implementierungsschritte

### 1. Resources anpassen:
```php
// In jeder Resource-Datei:
public static function getNavigationGroup(): ?string
{
    return __('admin.navigation.daily_operations'); // Beispiel
}

public static function getNavigationSort(): ?int
{
    return 1; // Position innerhalb der Gruppe
}
```

### 2. Zugriffsrechte für Gruppen:
```php
// Für System & Monitoring
public static function canViewAny(): bool
{
    return auth()->user()?->hasAnyRole(['super_admin', 'admin']);
}

// Für Entwicklung
public static function canViewAny(): bool
{
    return auth()->user()?->hasRole('super_admin');
}
```

### 3. Versteckte/Überflüssige Seiten:
- Alle `RetellAgentEditor*` Varianten → Eine konsolidierte Version
- Mehrere Dashboard-Varianten → Nur Dashboard + SimpleDashboard behalten
- Test-Pages → In "Entwicklung" Group verschieben

### 4. Navigation Icons vereinheitlichen:
```php
// Konsistente Heroicons verwenden
'heroicon-o-phone' // für Anrufe
'heroicon-o-calendar' // für Termine
'heroicon-o-users' // für Kunden
// etc.
```

## 📝 Notizen

1. **Sprachkonsistenz**: Alle Labels müssen deutsch sein (via Translation-Keys)
2. **Rollen-basierte Sichtbarkeit**: Test/Debug nur für Entwickler
3. **Performance**: Weniger Navigation Groups = schnellere Ladezeit
4. **Mobile-First**: Wichtigste Funktionen oben für mobile Nutzung

## ⚠️ Wichtige Hinweise

- Diese Struktur reduziert die Komplexität erheblich
- Normale Benutzer sehen nur relevante Menüpunkte
- Test-Funktionen sind sicher vor Endnutzern versteckt
- Die Struktur ist erweiterbar für zukünftige Features