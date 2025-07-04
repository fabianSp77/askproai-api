# Context7 Integration Guide für AskProAI

## 🎯 Überblick

Context7 ist jetzt in AskProAI integriert und bietet Zugriff auf aktuelle Dokumentation und Code-Beispiele für alle verwendeten Bibliotheken. Dies hilft bei der schnellen Lösung von Problemen und der Implementierung neuer Features.

## 📚 Verfügbare Bibliotheken

### Kritische Bibliotheken
- **Laravel** (`/context7/laravel`) - 5.724 Code-Snippets, Trust Score: 10
- **Filament** (`/filamentphp/filament`) - 2.337 Code-Snippets, Trust Score: 8.3
- **Retell AI** (`/context7/docs_retellai_com`) - 405 Code-Snippets, Trust Score: 8
- **Cal.com** (`/calcom/cal.com`) - 388 Code-Snippets, Trust Score: 9.2

### Weitere wichtige Bibliotheken
- **Laravel Horizon** (`/laravel/horizon`) - Queue Management
- **Livewire** (`/livewire/livewire`) - Dynamic UI Components

## 🚀 Verwendung

### 1. Artisan Command

```bash
# Liste aller verfügbaren Bibliotheken
php artisan context7:docs list

# Nach einer Bibliothek suchen
php artisan context7:docs search laravel

# Dokumentation abrufen
php artisan context7:docs get --library-id=/laravel/docs --topic="multi-tenancy"

# Code-Beispiele suchen
php artisan context7:docs examples "webhook signature" --library-id=/context7/docs_retellai_com
```

### 2. In PHP-Code

```php
use App\Services\Context7Service;

$context7 = app(Context7Service::class);

// Bibliothek suchen
$libraries = $context7->searchLibrary('filament');

// Dokumentation abrufen
$docs = $context7->getLibraryDocs('/filamentphp/filament', 'livewire v3', 5000);

// Code-Beispiele suchen
$examples = $context7->searchCodeExamples('/retell/docs', 'webhook');
```

### 3. MCP-Server Integration

Der RetellMCPServer hat jetzt automatische Dokumentationshilfe:

```php
$retellMCP = app(RetellMCPServer::class);

// Hilfe bei Problemen
$help = $retellMCP->getHelpDocumentation([
    'issue' => 'webhook signature verification fails'
]);

// Automatisches Troubleshooting mit Dokumentation
$troubleshoot = $retellMCP->troubleshoot([
    'company_id' => 1
]);
```

## 💡 Konkrete Anwendungsfälle

### 1. Retell.ai Webhook-Probleme lösen

```bash
# Dokumentation und Lösungsvorschläge abrufen
php artisan context7:docs examples "webhook signature" --library-id=/context7/docs_retellai_com

# Oder in Code:
$help = $retellMCP->getHelpDocumentation([
    'issue' => 'webhook signature verification'
]);

// Gibt zurück:
// - Dokumentation zu Webhook-Signatur-Verifizierung
// - Code-Beispiele aus unserem Projekt
// - Konkrete Lösungsvorschläge
```

### 2. Filament Livewire v3 Kompatibilität

```bash
# Aktuelle Filament Dokumentation für Livewire v3
php artisan context7:docs get --library-id=/filamentphp/filament --topic="livewire v3"

# Migration Guide und Best Practices
php artisan context7:docs examples "livewire migration" --library-id=/filamentphp/filament
```

### 3. Multi-Tenancy Best Practices

```php
// Laravel Multi-Tenancy Patterns
$docs = $context7->getLibraryDocs('/context7/laravel', 'multi-tenancy');

// Zeigt:
// - Global Scopes für Tenant-Isolation
// - Repository Pattern mit Tenant-Scopes
// - Middleware für Tenant-Resolution
```

### 4. Cal.com API v2 Migration

```bash
# Cal.com v2 API Dokumentation
php artisan context7:docs get --library-id=/calcom/cal.com --topic="api v2"

# Beispiele für Booking-Endpoints
php artisan context7:docs examples "create booking" --library-id=/calcom/cal.com
```

## 🔧 Integration in Entwicklungsworkflow

### Bei Fehlern
1. Fehlermeldung kopieren
2. `php artisan context7:docs search "fehlermeldung"`
3. Relevante Bibliothek identifizieren
4. Dokumentation und Beispiele abrufen

### Bei neuen Features
1. Feature-Anforderung analysieren
2. Relevante Bibliotheken mit Context7 finden
3. Best Practices und Patterns aus Dokumentation nutzen
4. Code-Beispiele als Vorlage verwenden

### Bei Performance-Problemen
```php
// Query Optimization Patterns
$docs = $context7->getLibraryDocs('/context7/laravel', 'query optimization');

// Caching Strategies
$docs = $context7->getLibraryDocs('/context7/laravel', 'caching');
```

## 📊 Erwarteter Nutzen

- **30-50% Zeitersparnis** bei Dokumentationssuche
- **Weniger Fehler** durch aktuelle Best Practices
- **Schnellere Entwicklung** mit Code-Snippets
- **Bessere Code-Qualität** durch Framework-konforme Implementierungen

## 🛠️ Wartung & Updates

### Cache-Management
- Dokumentation wird 1 Stunde gecacht
- Bei Bedarf Cache leeren: `php artisan cache:clear`

### Neue Bibliotheken hinzufügen
In `app/Services/Context7Service.php`:
```php
protected array $projectLibraries = [
    'neue-lib' => '/org/project-id',
    // ...
];
```

## 🐛 Troubleshooting

### Context7 Service nicht verfügbar
```bash
# Service Provider prüfen
php artisan tinker
>>> app()->bound(\App\Services\Context7Service::class)
# Sollte true zurückgeben
```

### Keine Dokumentation gefunden
- Library ID überprüfen mit `php artisan context7:docs list`
- Topic-Parameter anpassen oder weglassen
- Max-Tokens erhöhen für mehr Inhalt

## 📝 Beispiel-Session

```bash
# Problem: Retell.ai Calls werden nicht importiert
$ php artisan context7:docs search "retell calls import"

# Findet: Retell AI Library
$ php artisan context7:docs get --library-id=/context7/docs_retellai_com --topic="list calls api"

# Zeigt API v2 Endpoints und Beispiele
$ php artisan context7:docs examples "list calls" --library-id=/context7/docs_retellai_com

# Lösung: RetellV2Service verwenden mit /v2/list-calls Endpoint
```

## 🎯 Next Steps

1. **Team-Schulung**: Alle Entwickler in Context7-Nutzung einweisen
2. **IDE-Integration**: Context7 in VS Code/PHPStorm integrieren
3. **CI/CD**: Dokumentations-Freshness in Pipeline prüfen
4. **Monitoring**: Context7-Nutzung tracken für ROI-Messung

---

**Hinweis**: Diese Integration macht externe Dokumentationssuche oft überflüssig und stellt sicher, dass immer mit aktuellen Best Practices gearbeitet wird.