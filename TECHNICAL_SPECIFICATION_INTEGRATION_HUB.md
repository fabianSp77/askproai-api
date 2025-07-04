# Technische Spezifikation: Integration Hub

## 🎯 Übersicht
Eine zentrale Verwaltungsseite für alle externen Integrationen (Cal.com, Retell.ai).

## 📐 Architektur

### Datenbankschema
```sql
-- Neue zentrale Tabelle für Integrationen
CREATE TABLE integration_configs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES companies(id),
    provider VARCHAR(50) NOT NULL, -- 'calcom', 'retell', 'stripe', etc.
    api_key TEXT, -- Verschlüsselt
    webhook_secret TEXT, -- Verschlüsselt
    settings JSONB DEFAULT '{}',
    is_active BOOLEAN DEFAULT false,
    last_sync_at TIMESTAMP,
    last_error TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(company_id, provider)
);

-- Index für Performance
CREATE INDEX idx_integration_configs_company_provider ON integration_configs(company_id, provider);
```

### Livewire Component Struktur
```php
namespace App\Filament\Admin\Pages;

class IntegrationHub extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Integration Hub';
    protected static ?string $navigationGroup = 'Integrationen';
    protected static ?int $navigationSort = 10;
    
    // State Management
    public ?array $calcomData = [];
    public ?array $retellData = [];
    public string $activeTab = 'overview';
    
    // Form Schemas
    protected function getCalcomFormSchema(): array
    {
        return [
            TextInput::make('api_key')
                ->label('Cal.com API Schlüssel')
                ->password()
                ->revealable()
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->validateCalcomKey($state)),
                
            TextInput::make('team_slug')
                ->label('Team Slug')
                ->placeholder('mein-team')
                ->helperText('Nur bei Team-Accounts benötigt'),
                
            Select::make('default_event_type_id')
                ->label('Standard Event Type')
                ->options(fn () => $this->getEventTypeOptions())
                ->searchable()
                ->reactive(),
                
            Section::make('Synchronisation')
                ->schema([
                    Toggle::make('auto_sync_enabled')
                        ->label('Automatische Synchronisation'),
                    Select::make('sync_interval')
                        ->label('Sync-Intervall')
                        ->options([
                            '15' => 'Alle 15 Minuten',
                            '30' => 'Alle 30 Minuten',
                            '60' => 'Stündlich',
                            '1440' => 'Täglich'
                        ])
                        ->visible(fn ($get) => $get('auto_sync_enabled'))
                ])
        ];
    }
    
    protected function getRetellFormSchema(): array
    {
        return [
            TextInput::make('api_key')
                ->label('Retell.ai API Schlüssel')
                ->password()
                ->revealable()
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->validateRetellKey($state)),
                
            Select::make('default_agent_id')
                ->label('Standard Agent')
                ->options(fn () => $this->getAgentOptions())
                ->searchable()
                ->reactive(),
                
            Section::make('Webhook Konfiguration')
                ->schema([
                    TextInput::make('webhook_url')
                        ->label('Webhook URL')
                        ->disabled()
                        ->default(fn () => url('/api/retell/webhook')),
                    Actions::make([
                        Action::make('copy_webhook_url')
                            ->label('URL kopieren')
                            ->icon('heroicon-o-clipboard')
                            ->action(fn () => $this->copyToClipboard())
                    ])
                ])
        ];
    }
}
```

## 🔄 Datenfluss

### 1. Initial Load
```
User öffnet Integration Hub
    ↓
Lade integration_configs für company_id
    ↓
Populate Forms mit existierenden Daten
    ↓
Check Verbindungsstatus für jeden Provider
    ↓
Zeige Status Overview
```

### 2. Konfiguration speichern
```
User ändert API Key
    ↓
Validiere Key mit Provider API
    ↓
Verschlüssele sensitive Daten
    ↓
Speichere in integration_configs
    ↓
Trigger Sync wenn aktiviert
    ↓
Update UI Status
```

### 3. Live Status Updates
```php
// Polling alle 30 Sekunden
wire:poll.30s="refreshStatus"

public function refreshStatus()
{
    foreach (['calcom', 'retell'] as $provider) {
        $this->checkProviderStatus($provider);
    }
}
```

## 🎨 UI/UX Design

### Tab-basierte Navigation
```blade
<div class="integration-hub-container">
    <!-- Tab Navigation -->
    <nav class="border-b border-gray-200">
        <div class="flex space-x-8">
            <button wire:click="setActiveTab('overview')" 
                    class="{{ $activeTab === 'overview' ? 'border-primary-500 text-primary-600' : '' }}">
                Übersicht
            </button>
            <button wire:click="setActiveTab('calcom')">
                Cal.com
            </button>
            <button wire:click="setActiveTab('retell')">
                Retell.ai
            </button>
        </div>
    </nav>
    
    <!-- Tab Content -->
    <div class="mt-6">
        @if($activeTab === 'overview')
            @include('filament.admin.pages.integration-hub.overview')
        @elseif($activeTab === 'calcom')
            @include('filament.admin.pages.integration-hub.calcom')
        @elseif($activeTab === 'retell')
            @include('filament.admin.pages.integration-hub.retell')
        @endif
    </div>
</div>
```

### Status Cards (Overview Tab)
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Cal.com Status -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Cal.com</h3>
            @if($calcomConnected)
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                    Verbunden
                </span>
            @else
                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-sm">
                    Nicht verbunden
                </span>
            @endif
        </div>
        
        <dl class="space-y-2">
            <div class="flex justify-between">
                <dt class="text-gray-500">Event Types:</dt>
                <dd class="font-medium">{{ $calcomStats['event_types'] ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Letzte Sync:</dt>
                <dd class="text-sm">{{ $calcomStats['last_sync'] ?? 'Nie' }}</dd>
            </div>
        </dl>
        
        <div class="mt-4">
            <x-filament::button wire:click="syncCalcom" size="sm" outlined>
                Jetzt synchronisieren
            </x-filament::button>
        </div>
    </div>
    
    <!-- Retell.ai Status (similar structure) -->
</div>
```

## 🔧 Business Logic

### Validierung & Abhängigkeiten
```php
public function validateDependencies(): array
{
    $issues = [];
    
    // Check Cal.com dependencies
    if ($this->hasCalcomIntegration()) {
        if (!$this->company->branches()->exists()) {
            $issues[] = 'Cal.com benötigt mindestens eine Filiale';
        }
        if (!$this->company->staff()->exists()) {
            $issues[] = 'Cal.com benötigt mindestens einen Mitarbeiter';
        }
    }
    
    // Check Retell dependencies
    if ($this->hasRetellIntegration()) {
        if (!$this->company->branches()->exists()) {
            $issues[] = 'Retell.ai benötigt mindestens eine Filiale';
        }
    }
    
    return $issues;
}
```

### Auto-Discovery Features
```php
protected function discoverCalcomData(): void
{
    try {
        $calcomMCP = app(CalcomMCPServer::class);
        
        // Discover event types
        $eventTypes = $calcomMCP->getEventTypes(['company_id' => $this->company->id]);
        
        // Discover users
        $users = $calcomMCP->getUsers(['company_id' => $this->company->id]);
        
        // Auto-map to local data
        $this->autoMapEventTypes($eventTypes);
        $this->autoMapUsers($users);
        
    } catch (\Exception $e) {
        $this->addError('calcom', 'Discovery fehlgeschlagen: ' . $e->getMessage());
    }
}
```

## 🧪 Testing Requirements

### Unit Tests
- API Key Verschlüsselung/Entschlüsselung
- Validierungslogik
- Dependency Checks

### Integration Tests
- Cal.com API Verbindung
- Retell.ai API Verbindung
- Sync-Prozesse

### E2E Tests
- Complete Integration Setup Flow
- Status Updates
- Error Handling

## 📊 Metriken & Monitoring

```php
// Track integration health
IntegrationMetrics::record([
    'company_id' => $this->company->id,
    'provider' => 'calcom',
    'action' => 'sync',
    'success' => true,
    'duration_ms' => $duration,
    'records_synced' => $count
]);
```

## 🚀 Deployment Checklist

- [ ] Datenbank-Migration ausführen
- [ ] integration_configs Tabelle erstellen
- [ ] Bestehende API Keys migrieren
- [ ] Verschlüsselung testen
- [ ] Permissions prüfen
- [ ] UI in allen Browsern testen
- [ ] Monitoring einrichten