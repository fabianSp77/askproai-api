# Implementierungsplan für verbleibende Features

## Analyse-Ergebnis der vorhandenen Komponenten

### ✅ Bereits vorhanden:

1. **Call Routing & Weiterleitung**
   - `IntelligentCallRouter` mit umfangreichen Routing-Strategien
   - Skill-basiertes Routing
   - Sprach-basiertes Routing (language_match scoring)
   - Performance-basiertes Routing
   - Workload-Balancing

2. **Callback-System**
   - `CallbackRequest` Model mit vollständiger Implementation
   - `CallbackService` mit Prioritäts-Management
   - Auto-Close Funktionalität
   - Email/SMS Benachrichtigungen
   - Callback-Statistiken

3. **Analytics & Reporting**
   - `EventAnalyticsDashboard` mit umfangreichen Metriken
   - `CallAnalyticsWidget` mit Real-Time Statistiken
   - Heatmap-Visualisierung
   - Top-Performer Rankings
   - Revenue Analytics
   - No-Show Tracking bereits implementiert

4. **Sentiment Analysis**
   - Database-Feld `sentiment_score` in calls Tabelle
   - Frontend-Komponenten für Sentiment-Visualisierung

5. **Sprach-Support**
   - Retell API unterstützt 30+ Sprachen nativ
   - Language-Scoring im CallRouter
   - Sprach-Familien-Erkennung (de, de-DE, de-AT, de-CH)

## 🔴 Fehlende Features & Implementierungsplan

### 1. **Direktweiterleitung zu +491604366218**

#### 1.1 Retell Custom Function für Weiterleitung
```php
// app/Services/Retell/CustomFunctions/CallTransferFunction.php
class CallTransferFunction extends BaseCustomFunction
{
    public function getName(): string
    {
        return 'transfer_call_to_owner';
    }
    
    public function getDescription(): string
    {
        return 'Leitet den Anruf direkt an den Geschäftsinhaber weiter';
    }
    
    public function getParameters(): array
    {
        return [
            'reason' => [
                'type' => 'string',
                'description' => 'Grund für die Weiterleitung',
                'required' => true
            ]
        ];
    }
    
    public function execute(array $parameters, CallContext $context): array
    {
        // Implementierung der Weiterleitung
        $transferNumber = '+491604366218';
        
        // Option 1: Direkte Weiterleitung via Retell
        return [
            'action' => 'transfer_call',
            'destination' => $transferNumber,
            'whisper_message' => "Anruf von {$context->getCallerNumber()} - Grund: {$parameters['reason']}"
        ];
        
        // Option 2: Callback-Request erstellen wenn besetzt
        if ($this->isNumberBusy($transferNumber)) {
            $this->createCallbackRequest($context, $parameters['reason']);
            return [
                'message' => 'Der Geschäftsinhaber ist momentan nicht erreichbar. Ein Rückruf wurde eingetragen.'
            ];
        }
    }
}
```

#### 1.2 Wartemusik während Weiterleitung
```php
// In RetellAgent Konfiguration
'voice_settings' => [
    'hold_music_url' => 'https://askproai.de/audio/wartemusik.mp3',
    'transfer_announcement' => 'Ich verbinde Sie jetzt mit dem Geschäftsinhaber. Einen Moment bitte.',
]
```

**Zeitaufwand**: 4 Stunden

### 2. **Erweiterte Analytics Features**

#### 2.1 Export-Funktionen
```php
// app/Filament/Admin/Pages/Actions/ExportAnalyticsAction.php
class ExportAnalyticsAction extends Action
{
    public function handle(array $data): StreamedResponse
    {
        $format = $data['format']; // csv, xlsx, pdf
        $dateRange = $data['date_range'];
        
        // Sammle alle Analytics-Daten
        $analytics = [
            'calls' => $this->getCallStatistics($dateRange),
            'appointments' => $this->getAppointmentStatistics($dateRange),
            'revenue' => $this->getRevenueStatistics($dateRange),
            'vip_customers' => $this->getVipCustomerStats($dateRange),
        ];
        
        return match($format) {
            'csv' => $this->exportToCsv($analytics),
            'xlsx' => $this->exportToExcel($analytics),
            'pdf' => $this->exportToPdf($analytics),
        };
    }
}
```

#### 2.2 VIP-Kunden Dashboard Widget
```php
// app/Filament/Admin/Widgets/VipCustomerWidget.php
class VipCustomerWidget extends Widget
{
    protected function getCards(): array
    {
        $vipCustomers = Customer::query()
            ->withCount(['appointments', 'calls'])
            ->having('appointments_count', '>=', 10) // VIP Threshold
            ->orHaving('lifetime_value', '>=', 1000)
            ->orderByDesc('lifetime_value')
            ->limit(10)
            ->get();
            
        return $vipCustomers->map(fn($customer) => [
            'name' => $customer->name,
            'phone' => $customer->masked_phone,
            'appointments' => $customer->appointments_count,
            'lifetime_value' => number_format($customer->lifetime_value, 2) . ' €',
            'last_contact' => $customer->last_contact_at?->diffForHumans(),
            'tags' => ['VIP', $customer->preferred_language ?? 'DE'],
        ])->toArray();
    }
}
```

**Zeitaufwand**: 6 Stunden

### 3. **Sprachliche Verbesserungen**

#### 3.1 Dialekt-Erkennung & Anpassung
```php
// app/Services/Retell/DialectDetector.php
class DialectDetector
{
    private array $dialectPatterns = [
        'bavarian' => [
            'patterns' => ['servus', 'griaß di', 'pfiat di', 'mei'],
            'formal_level' => 'informal',
            'response_style' => 'friendly_regional'
        ],
        'swabian' => [
            'patterns' => ['grüß gott', 'ade', 'gell'],
            'formal_level' => 'semi-formal',
            'response_style' => 'warm_professional'
        ],
        'berlinerisch' => [
            'patterns' => ['ick', 'wat', 'dit'],
            'formal_level' => 'casual',
            'response_style' => 'direct_friendly'
        ],
    ];
    
    public function detectDialect(string $transcript): ?array
    {
        $detectedDialects = [];
        
        foreach ($this->dialectPatterns as $dialect => $config) {
            $score = $this->calculateDialectScore($transcript, $config['patterns']);
            if ($score > 0.3) {
                $detectedDialects[$dialect] = $score;
            }
        }
        
        if (empty($detectedDialects)) {
            return null;
        }
        
        $mainDialect = array_key_first($detectedDialects);
        return $this->dialectPatterns[$mainDialect];
    }
}
```

#### 3.2 Höflichkeits-Level Anpassung
```php
// app/Services/Retell/PolitenessAdapter.php
class PolitenessAdapter
{
    public function adaptPrompt(string $basePrompt, array $context): string
    {
        $politenessLevel = $this->determinePolitenessLevel($context);
        
        $adaptations = [
            'very_formal' => [
                'greeting' => 'Guten Tag, hier spricht {agent_name} von {company}. Wie darf ich Ihnen behilflich sein?',
                'address' => 'Sie',
                'closing' => 'Ich danke Ihnen vielmals für Ihr Vertrauen.',
            ],
            'formal' => [
                'greeting' => 'Guten Tag, {agent_name} von {company}. Wie kann ich Ihnen helfen?',
                'address' => 'Sie',
                'closing' => 'Vielen Dank für Ihren Anruf.',
            ],
            'informal' => [
                'greeting' => 'Hallo, hier ist {agent_name}. Was kann ich für dich tun?',
                'address' => 'du',
                'closing' => 'Danke für deinen Anruf!',
            ],
        ];
        
        return $this->replacePromptVariables($basePrompt, $adaptations[$politenessLevel]);
    }
}
```

#### 3.3 Echtzeit Sentiment-Tracking
```php
// app/Services/Retell/SentimentAnalyzer.php
class SentimentAnalyzer
{
    public function analyzeRealtime(string $transcript, array $audioFeatures): float
    {
        // Kombiniere Text-Sentiment mit Audio-Features
        $textSentiment = $this->analyzeText($transcript);
        $audioSentiment = $this->analyzeAudioFeatures($audioFeatures);
        
        // Gewichtete Kombination
        return ($textSentiment * 0.6) + ($audioSentiment * 0.4);
    }
    
    private function analyzeText(string $text): float
    {
        // Sentiment-Keywords mit Gewichtung
        $positiveKeywords = [
            'danke' => 0.8, 'super' => 0.9, 'perfekt' => 1.0,
            'gerne' => 0.7, 'freue mich' => 0.9
        ];
        
        $negativeKeywords = [
            'problem' => -0.3, 'schlecht' => -0.8, 'unzufrieden' => -0.9,
            'beschwerde' => -0.7, 'ärger' => -0.8
        ];
        
        return $this->calculateWeightedScore($text, $positiveKeywords, $negativeKeywords);
    }
}
```

**Zeitaufwand**: 8 Stunden

### 4. **Integration in Retell Agent Konfiguration**

```php
// app/Services/Retell/AgentConfigurationBuilder.php
class AgentConfigurationBuilder
{
    public function buildConfiguration(Company $company, Branch $branch): array
    {
        return [
            'agent_name' => $branch->ai_agent_name ?? 'KI-Assistent',
            'language' => $company->primary_language ?? 'de',
            'response_engine' => [
                'type' => 'retell-llm',
                'llm_id' => $company->retell_llm_id,
            ],
            'voice_settings' => [
                'voice_id' => $this->getVoiceForLanguage($company->primary_language),
                'speed' => 1.0,
                'emotion' => 'friendly',
            ],
            'interruption_sensitivity' => 0.7,
            'backchannel_frequency' => 0.8,
            'backchannel_words' => ['Ja', 'Verstehe', 'Natürlich', 'Gerne'],
            'custom_functions' => [
                'transfer_call_to_owner',
                'check_availability',
                'book_appointment',
                'create_callback',
                'detect_sentiment',
                'adapt_politeness',
            ],
            'ambient_sound' => [
                'enabled' => true,
                'hold_music_url' => $company->hold_music_url,
            ],
            'error_handling' => [
                'max_retries' => 2,
                'fallback_action' => 'create_callback',
                'error_messages' => [
                    'de' => 'Entschuldigung, es gab ein technisches Problem. Kann ich einen Rückruf für Sie vereinbaren?',
                ],
            ],
        ];
    }
}
```

**Zeitaufwand**: 4 Stunden

## Gesamt-Implementierungsplan

### Phase 1: Direktweiterleitung (1 Tag)
- [ ] CallTransferFunction implementieren
- [ ] Wartemusik-Integration
- [ ] Callback bei Nichterreichbarkeit
- [ ] Testing mit echten Anrufen

### Phase 2: Analytics-Erweiterungen (1,5 Tage)
- [ ] Export-Funktionen (CSV, Excel, PDF)
- [ ] VIP-Kunden Widget
- [ ] Erweiterte Metriken
- [ ] Performance-Optimierung

### Phase 3: Sprachliche Verbesserungen (2 Tage)
- [ ] Dialekt-Erkennung
- [ ] Höflichkeits-Anpassung
- [ ] Echtzeit-Sentiment
- [ ] Mehrsprachige Prompt-Templates

### Phase 4: Integration & Testing (1 Tag)
- [ ] Agent-Konfiguration Update
- [ ] End-to-End Tests
- [ ] Performance-Tests
- [ ] Dokumentation

**Gesamt-Zeitaufwand**: 5,5 Arbeitstage

## Prioritäten-Empfehlung

1. **Sofort umsetzen**: Direktweiterleitung zu +491604366218 (kritisch für Geschäftsbetrieb)
2. **Kurzfristig**: VIP-Kunden Analytics (direkter Business Value)
3. **Mittelfristig**: Sprachliche Verbesserungen (Qualitätssteigerung)
4. **Optional**: Export-Funktionen (Nice-to-have)

## Technische Voraussetzungen

- Retell API v2 (bereits vorhanden ✅)
- Redis für Echtzeit-Features (vorhanden ✅)
- Storage für Wartemusik (S3 oder lokal)
- PDF-Generator für Exports (dompdf/snappy)

## Risiken & Mitigationen

1. **Weiterleitung blockiert Hauptnummer**
   - Lösung: Separate Outbound-Nummer für Weiterleitungen

2. **Dialekt-Erkennung ungenau**
   - Lösung: Konservative Schwellwerte, Fallback auf Standard-Deutsch

3. **Performance bei vielen Analytics**
   - Lösung: Caching, Background-Jobs, Datenbank-Indizes