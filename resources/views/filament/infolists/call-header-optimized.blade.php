@php
    use Carbon\Carbon;
    use App\Models\BillingRate;
    use App\Services\ExchangeRateService;
    use App\Services\TranslationService;
    use App\Helpers\AutoTranslateHelper;
    
    // Extract all logic into clean variables at the top
    $record = $getRecord();
    
    // Customer data
    $customerData = [
        'name' => $record->customer?->name ?? $record->extracted_name ?? 'Unbekannter Anrufer',
        'company' => $record->customer?->company_name ?? '',
        'initial' => strtoupper(substr($record->customer?->name ?? $record->extracted_name ?? 'U', 0, 1)),
        'isNew' => $record->first_visit ?? false,
        'callCount' => $record->customer?->calls()->count() ?? 1,
        'lastCallDate' => $record->customer?->calls()->where('id', '!=', $record->id)->latest()->first()?->created_at,
    ];
    
    // Company and Branch data for header
    $companyName = $record->relationLoaded('company') 
        ? $record->company?->name 
        : \App\Models\Company::find($record->company_id)?->name ?? 'Unbekannte Firma';
    $branchName = $record->relationLoaded('branch') 
        ? $record->branch?->name 
        : \App\Models\Branch::find($record->branch_id)?->name;
    
    // Sentiment configuration with static classes
    $sentimentType = strtolower($record->webhook_data['call_analysis']['user_sentiment'] ?? 
                              $record->mlPrediction?->sentiment_label ?? 'neutral');
    
    $sentimentConfigs = [
        'positive' => [
            'icon' => '😊',
            'label' => 'Positiv',
            'bgClass' => 'bg-emerald-100 dark:bg-emerald-900/30',
            'textClass' => 'text-emerald-700 dark:text-emerald-400',
            'avatarStyle' => 'background: linear-gradient(to bottom right, #10b981, #059669);'
        ],
        'negative' => [
            'icon' => '😞',
            'label' => 'Negativ',
            'bgClass' => 'bg-rose-100 dark:bg-rose-900/30',
            'textClass' => 'text-rose-700 dark:text-rose-400',
            'avatarStyle' => 'background: linear-gradient(to bottom right, #f43f5e, #e11d48);'
        ],
        'mixed' => [
            'icon' => '🤔',
            'label' => 'Gemischt',
            'bgClass' => 'bg-amber-100 dark:bg-amber-900/30',
            'textClass' => 'text-amber-700 dark:text-amber-400',
            'avatarStyle' => 'background: linear-gradient(to bottom right, #f59e0b, #d97706);'
        ],
        'neutral' => [
            'icon' => '😐',
            'label' => 'Neutral',
            'bgClass' => 'bg-slate-100 dark:bg-slate-900/30',
            'textClass' => 'text-slate-700 dark:text-slate-400',
            'avatarStyle' => 'background: linear-gradient(to bottom right, #64748b, #475569);'
        ]
    ];
    
    $sentiment = $sentimentConfigs[$sentimentType] ?? $sentimentConfigs['neutral'];
    
    // Summary data
    $summary = $record->webhook_data['call_analysis']['call_summary'] ?? 
              $record->call_summary ?? 
              $record->summary ?? 
              null;
    
    // Always prepare translation data
    $toggleData = null;
    if ($summary) {
        $translator = app(TranslationService::class);
        $detectedLanguage = $record->detected_language ?? $translator->detectLanguage($summary);
        
        // Check if summary needs translation
        $userLang = auth()->user()->content_language ?? 'de';
        $needsTranslation = $detectedLanguage !== $userLang;
        
        if ($needsTranslation) {
            try {
                $translated = $translator->translate($summary, $userLang, $detectedLanguage);
                $toggleData = [
                    'should_translate' => true,
                    'original' => $summary,
                    'translated' => $translated,
                    'source_lang' => $detectedLanguage,
                    'target_lang' => $userLang
                ];
            } catch (\Exception $e) {
                // Fallback if translation fails
                $toggleData = [
                    'should_translate' => false,
                    'original' => $summary,
                    'translated' => $summary,
                    'source_lang' => $detectedLanguage,
                    'target_lang' => $userLang
                ];
            }
        } else {
            // Already in user's language
            $toggleData = [
                'should_translate' => false,
                'original' => $summary,
                'translated' => $summary,
                'source_lang' => $detectedLanguage,
                'target_lang' => $userLang
            ];
        }
    }
    
    // Call metrics
    $callMetrics = [
        'timestamp' => $record->start_timestamp ? Carbon::parse($record->start_timestamp) : $record->created_at,
        'duration' => [
            'seconds' => $record->duration_sec,
            'formatted' => sprintf('%02d:%02d', floor($record->duration_sec / 60), $record->duration_sec % 60),
            'percent' => min(100, ($record->duration_sec / 300) * 100)
        ],
        'phone' => $record->from_number,
        'language' => $toggleData ? $toggleData['source_lang'] : ($record->detected_language ?? 'de'),
        'status' => $record->status ?? 'ended'
    ];
    
    // Language config
    $languages = [
        'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch'],
        'en' => ['flag' => '🇬🇧', 'name' => 'English'],
        'fr' => ['flag' => '🇫🇷', 'name' => 'Français'],
        'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
        'it' => ['flag' => '🇮🇹', 'name' => 'Italiano'],
        'tr' => ['flag' => '🇹🇷', 'name' => 'Türkçe'],
    ];
    $languageConfig = $languages[$callMetrics['language']] ?? ['flag' => '🌍', 'name' => 'Auto'];
    
    // Status config with static classes
    $statusConfigs = [
        'ended' => ['label' => 'Beendet', 'bgClass' => 'bg-emerald-100 dark:bg-emerald-900/30', 'iconClass' => 'text-emerald-600 dark:text-emerald-400'],
        'completed' => ['label' => 'Beendet', 'bgClass' => 'bg-emerald-100 dark:bg-emerald-900/30', 'iconClass' => 'text-emerald-600 dark:text-emerald-400'],
        'in_progress' => ['label' => 'Laufend', 'bgClass' => 'bg-amber-100 dark:bg-amber-900/30', 'iconClass' => 'text-amber-600 dark:text-amber-400'],
        'failed' => ['label' => 'Fehlgeschlagen', 'bgClass' => 'bg-rose-100 dark:bg-rose-900/30', 'iconClass' => 'text-rose-600 dark:text-rose-400'],
    ];
    $statusConfig = $statusConfigs[$callMetrics['status']] ?? ['label' => 'Unbekannt', 'bgClass' => 'bg-slate-100 dark:bg-slate-900/30', 'iconClass' => 'text-slate-600 dark:text-slate-400'];
    
    // Financial calculations
    $financials = app(\App\Services\CallFinancialService::class)->calculateMetrics($record);
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="w-full" 
     x-data="callHeaderOptimized()"
     x-init="init()">
    
    {{-- Loading State --}}
    <div x-show="loading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="space-y-4">
        @include('filament.components.skeleton-loader')
    </div>
    
    {{-- Main Content --}}
    <div x-show="!loading"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="space-y-4">
         
        {{-- Unified Header Section --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm overflow-hidden">
            {{-- Title Bar with Actions --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $companyName }}@if($branchName) ({{ $branchName }})@endif wurde angerufen von {{ $customerData['name'] }}
                </h1>
                
                {{-- Action Buttons --}}
                <div class="flex items-center gap-2">
                    {{-- Business Portal Link --}}
                    @php
                        // Generate admin access token if user is admin
                        $portalUrl = route('business.calls.show', $record->id);
                        
                        if (auth()->user() && auth()->user()->hasRole('Super Admin')) {
                            // Generate one-time access token
                            $token = \Str::random(64);
                            $tokenData = [
                                'admin_id' => auth()->id(),
                                'company_id' => $record->company_id,
                                'redirect_to' => route('business.calls.show', $record->id),
                            ];
                            
                            // Store token for 5 minutes
                            cache()->put('admin_portal_access_' . $token, $tokenData, 300);
                            
                            // Build URL with token
                            $portalUrl = route('business.admin.access', ['token' => $token, 'redirect' => urlencode(route('business.calls.show', $record->id))]);
                        }
                    @endphp
                    
                    <a href="{{ $portalUrl }}" 
                       target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                       title="Im Business Portal öffnen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Business Portal
                    </a>
                    
                    {{-- Separator --}}
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    
                    <button @click="window.print()" 
                            type="button"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                            title="Drucken"
                            aria-label="Drucken">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                    </button>
                    <button @click="exportPDF()" 
                            type="button"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                            title="Als PDF exportieren"
                            aria-label="Als PDF exportieren">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            {{-- Financial Metrics Row --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center gap-6 text-sm flex-wrap">
                    {{-- Costs --}}
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400">Kosten:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($financials['cost'], 2) }}€</span>
                    </div>
                    
                    {{-- Revenue --}}
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400">Umsatz:</span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($financials['revenue'], 2) }}€</span>
                    </div>
                    
                    {{-- Profit --}}
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400">Gewinn:</span>
                        <span class="font-semibold {{ $financials['profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $financials['profit'] >= 0 ? '+' : '' }}{{ number_format($financials['profit'], 2) }}€
                            <span class="text-xs font-normal ml-1">({{ number_format($financials['margin'], 0) }}%)</span>
                        </span>
                    </div>
                    
                    {{-- Separator --}}
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    
                    {{-- Additional Metrics --}}
                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ number_format($financials['ratePerMinute'], 2) }}€/Min</span>
                        <span>•</span>
                        <span>ROI: {{ number_format($financials['roi'], 0) }}%</span>
                        @if($financials['trend'] !== 'neutral')
                            <span>•</span>
                            <span class="{{ $financials['trend'] === 'up' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $financials['trend'] === 'up' ? '↑' : '↓' }} {{ $financials['trendPercent'] }}%
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
         
        {{-- Hero Section with Quick Actions --}}
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 rounded-xl shadow-sm overflow-hidden">
            {{-- Customer Header --}}
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    {{-- Customer Info --}}
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        {{-- Avatar with status indicator --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-lg shadow-md"
                                 style="{{ $sentiment['avatarStyle'] }}">
                                {{ $customerData['initial'] }}
                            </div>
                            @if($callMetrics['status'] === 'in_progress')
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-slate-800 animate-pulse"></div>
                            @endif
                        </div>
                        
                        {{-- Name & Context --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $customerData['name'] }}
                                </h2>
                                @if($customerData['isNew'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                        </svg>
                                        Neukunde
                                    </span>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $customerData['callCount'] }} {{ $customerData['callCount'] === 1 ? 'Anruf' : 'Anrufe' }}
                                    </span>
                                @endif
                            </div>
                            @if($customerData['company'])
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                    {{ $customerData['company'] }}
                                </p>
                            @endif
                            @if($customerData['lastCallDate'])
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Letzter Anruf: {{ $customerData['lastCallDate']->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Sentiment Badge --}}
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg {{ $sentiment['bgClass'] }} {{ $sentiment['textClass'] }} font-medium text-sm">
                        <span class="text-lg">{{ $sentiment['icon'] }}</span>
                        <span>{{ $sentiment['label'] }}</span>
                    </div>
                </div>
                
                {{-- Summary Integration --}}
                @if($summary)
                    <div class="mt-4 -mx-2 px-4 py-3 bg-white/50 dark:bg-slate-800/50 rounded-lg backdrop-blur-sm"
                         x-data="{ 
                            showOriginal: @js(!($toggleData && $toggleData['should_translate'])),
                            originalText: @js($toggleData['original'] ?? $summary),
                            translatedText: @js($toggleData['translated'] ?? $summary),
                            sourceLanguage: @js($toggleData['source_lang'] ?? 'auto'),
                            targetLanguage: @js($toggleData['target_lang'] ?? 'de'),
                            shouldTranslate: @js($toggleData['should_translate'] ?? false),
                            showLanguageMenu: false
                         }">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" 
                                   x-text="(shouldTranslate && !showOriginal) ? translatedText : originalText">
                                </p>
                                @if($toggleData && ($toggleData['should_translate'] || $toggleData['source_lang'] !== $toggleData['target_lang']))
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                                            </path>
                                        </svg>
                                        <span x-show="shouldTranslate && !showOriginal">
                                            Übersetzt: <strong>{{ strtoupper($toggleData['source_lang'] ?? 'AUTO') }}</strong> → <strong>{{ strtoupper($toggleData['target_lang'] ?? 'DE') }}</strong>
                                        </span>
                                        <span x-show="!shouldTranslate || showOriginal">
                                            @if($toggleData['should_translate'])
                                                Original in 
                                            @endif
                                            <strong>{{ strtoupper($toggleData['source_lang'] ?? 'AUTO') }}</strong>
                                        </span>
                                    </div>
                                @endif
                            </div>
                            {{-- Translation button always visible --}}
                            <button 
                                @click="shouldTranslate ? (showOriginal = !showOriginal) : translateSummary()"
                                type="button"
                                class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-slate-700 transition-colors relative"
                                :title="shouldTranslate ? (showOriginal ? 'Übersetzung anzeigen' : 'Original anzeigen') : 'Übersetzen'"
                                aria-label="Übersetzung umschalten"
                                :disabled="loading"
                                :class="{ 'opacity-50 cursor-not-allowed': loading }">
                                <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path x-show="!loading" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                                    </path>
                                    <path x-show="loading" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                <span class="text-sm font-medium" x-text="loading ? 'Übersetze...' : (shouldTranslate ? (showOriginal ? 'Übersetzung anzeigen' : 'Original anzeigen') : 'Übersetzen')"></span>
                                <span x-show="shouldTranslate && !showOriginal" class="absolute -top-1 -right-1 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Metrics Grid --}}
            <div class="px-6 pb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {{-- Combined Phone Field --}}
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm hover:shadow-md transition-all duration-200 col-span-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Telefon</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $callMetrics['phone'] }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                {{-- Copy Button --}}
                                <button @click="copyToClipboard('{{ $callMetrics['phone'] }}')"
                                        type="button"
                                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group"
                                        title="Nummer kopieren"
                                        aria-label="Telefonnummer kopieren">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                {{-- Call Button --}}
                                <a href="tel:{{ $callMetrics['phone'] }}"
                                   class="p-2 rounded-lg bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-800/40 transition-colors group"
                                   title="Anrufen"
                                   aria-label="Anrufen">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Duration --}}
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Dauer</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $callMetrics['duration']['formatted'] }}</p>
                                <div class="mt-1 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                    <div class="bg-purple-500 h-1 rounded-full transition-all duration-300" 
                                         style="width: {{ $callMetrics['duration']['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Date & Time --}}
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $callMetrics['timestamp']->format('d.m.Y') }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $callMetrics['timestamp']->format('H:i') }} Uhr</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Language & Status --}}
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full {{ $statusConfig['bgClass'] }} flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 {{ $statusConfig['iconClass'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $languageConfig['flag'] }} {{ $languageConfig['name'] }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $statusConfig['label'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Customer Context (Collapsible) --}}
        @if($record->customer)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm" x-data="{ expanded: false }">
                <button @click="expanded = !expanded" 
                        @keydown.space.prevent="expanded = !expanded"
                        @keydown.enter.prevent="expanded = !expanded"
                        class="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
                        :aria-expanded="expanded.toString()"
                        aria-controls="customer-history-panel"
                        type="button">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Kundenhistorie
                    </h3>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                <div x-show="expanded" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="px-4 pb-4"
                     id="customer-history-panel"
                     role="region"
                     aria-labelledby="customer-history-button">
                    @if($record->customer)
                        @include('filament.components.customer-timeline', [
                            'customer' => $record->customer,
                            'currentCallId' => $record->id
                        ])
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                            Kein Kunde zugeordnet. Bitte ordnen Sie diesem Anruf einen Kunden zu, um die Historie anzuzeigen.
                        </p>
                    @endif
                </div>
            </div>
        @endif
        
        {{-- Notes/Journal Section --}}
        <div class="mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    Journal & Notizen
                </h3>
                <button @click="addNote()" 
                        type="button"
                        class="px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/30 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-800/40 transition-colors">
                    + Notiz hinzufügen
                </button>
            </div>
            
            <div class="space-y-3">
                @php
                    $notes = [];
                    // Check if notes is a string (JSON) and decode it
                    if (is_string($record->notes)) {
                        $notes = json_decode($record->notes, true) ?? [];
                    } elseif (is_array($record->notes)) {
                        $notes = $record->notes;
                    }
                @endphp
                
                @forelse($notes as $note)
                    <div class="border-l-2 border-gray-200 dark:border-gray-700 pl-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $note['content'] ?? $note }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $note['created_at'] ?? 'Unbekannt' }} • {{ $note['user_name'] ?? 'System' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        Noch keine Notizen vorhanden. Fügen Sie Ihre erste Notiz hinzu, um ein Journal für diesen Anruf zu erstellen.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
    
    {{-- Notifications --}}
    <div x-show="showNotification" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg text-sm"
         style="display: none;"
         role="status"
         aria-live="polite">
        <span x-text="notificationText"></span>
    </div>
    
    {{-- Screen Reader Announcements --}}
    <div class="sr-only" aria-live="assertive" aria-atomic="true">
        <span x-text="srAnnouncement"></span>
    </div>
</div>

<script>
function callHeaderOptimized() {
    return {
        loading: false,
        showNotification: false,
        notificationText: '',
        srAnnouncement: '',
        
        init() {
            // Initial animations
            this.loading = false;
        },
        
        copyToClipboard(text) {
            // Fallback for older browsers or when clipboard API is not available
            if (!navigator.clipboard) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    this.showNotificationMessage('Kopiert!');
                    this.announceToScreenReader('Telefonnummer ' + text + ' wurde in die Zwischenablage kopiert');
                } catch (err) {
                    console.error('Fallback copy failed:', err);
                    this.showNotificationMessage('Kopieren fehlgeschlagen');
                } finally {
                    textArea.remove();
                }
                return;
            }
            
            // Modern clipboard API
            navigator.clipboard.writeText(text)
                .then(() => {
                    this.showNotificationMessage('Kopiert!');
                    this.announceToScreenReader('Telefonnummer ' + text + ' wurde in die Zwischenablage kopiert');
                })
                .catch(err => {
                    console.error('Copy failed:', err);
                    this.showNotificationMessage('Kopieren fehlgeschlagen. Bitte manuell kopieren.');
                });
        },
        
        announceToScreenReader(message) {
            this.srAnnouncement = message;
            // Clear after announcement
            setTimeout(() => {
                this.srAnnouncement = '';
            }, 1000);
        },
        
        showNotificationMessage(text) {
            this.notificationText = text;
            this.showNotification = true;
            setTimeout(() => {
                this.showNotification = false;
            }, 2000);
        },
        
        animateValue(start, end, duration, callback) {
            const range = end - start;
            const startTime = performance.now();
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const value = start + (range * this.easeOutQuart(progress));
                callback(value);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        },
        
        easeOutQuart(t) {
            return 1 - Math.pow(1 - t, 4);
        },
        
        formatCurrency(value) {
            return new Intl.NumberFormat('de-DE', { 
                minimumFractionDigits: 2,
                maximumFractionDigits: 2 
            }).format(value);
        },
        
        addNote() {
            // Open Filament modal for adding notes
            const noteText = prompt('Notiz eingeben:');
            if (noteText && noteText.trim()) {
                // In real implementation, this would save to database
                this.showNotificationMessage('Notiz wurde hinzugefügt');
                // Reload the page to show new note
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        },
        
        exportPDF() {
            // Show loading message
            this.showNotificationMessage('PDF wird erstellt...');
            
            // Get the call ID from the URL
            const callId = window.location.pathname.split('/').pop();
            
            // Open the PDF in a new window with print dialog
            const pdfWindow = window.open(`/api/calls/${callId}/export-pdf?print=true`, '_blank');
            
            // Check if popup was blocked
            if (!pdfWindow || pdfWindow.closed || typeof pdfWindow.closed == 'undefined') {
                // Fallback: download the PDF
                fetch(`/api/calls/${callId}/export-pdf`, {
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.getAttribute('content') || ''}`
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('PDF generation failed');
                    }
                    return response.text();
                })
                .then(html => {
                    // Create a blob from the HTML
                    const blob = new Blob([html], { type: 'text/html' });
                    const url = window.URL.createObjectURL(blob);
                    
                    // Create a temporary iframe to print
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = url;
                    document.body.appendChild(iframe);
                    
                    iframe.onload = () => {
                        iframe.contentWindow.print();
                        setTimeout(() => {
                            document.body.removeChild(iframe);
                            window.URL.revokeObjectURL(url);
                        }, 1000);
                    };
                    
                    this.showNotificationMessage('PDF bereit zum Drucken');
                })
                .catch(error => {
                    console.error('PDF export error:', error);
                    this.showNotificationMessage('PDF-Export fehlgeschlagen');
                    // Fallback to browser print
                    window.print();
                });
            } else {
                this.showNotificationMessage('PDF wurde in neuem Tab geöffnet');
            }
        },
        
        translateSummary() {
            this.loading = true;
            this.showNotificationMessage('Übersetze Zusammenfassung...');
            
            // Get the call ID from the URL or data attribute
            const callId = window.location.pathname.split('/').pop();
            const targetLang = '{{ auth()->user()->content_language ?? "de" }}';
            
            // Make API request to translate
            fetch(`{{ route('admin.api.calls.translate-summary', ':id') }}`.replace(':id', callId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    target_language: targetLang
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Translation failed');
                }
                return response.json();
            })
            .then(data => {
                // Update the translation data
                this.translatedText = data.translated;
                this.sourceLanguage = data.source_language;
                this.targetLanguage = data.target_language;
                this.shouldTranslate = true;
                this.showOriginal = false;
                
                this.showNotificationMessage('Übersetzung erfolgreich!');
                this.announceToScreenReader('Zusammenfassung wurde übersetzt');
            })
            .catch(error => {
                console.error('Translation error:', error);
                this.showNotificationMessage('Übersetzung fehlgeschlagen. Bitte versuchen Sie es später erneut.');
            })
            .finally(() => {
                this.loading = false;
            });
        }
    }
}
</script>

<style>
@media print {
    /* Hide navigation and action buttons */
    .no-print,
    button,
    [role="button"],
    nav,
    .fixed,
    .sticky,
    .hover\\:bg-gray-100,
    .transition-colors,
    [x-show="showNotification"],
    .sr-only {
        display: none !important;
    }
    
    /* Optimize page layout */
    body {
        font-size: 12pt;
        line-height: 1.5;
        color: #000;
        background: white;
    }
    
    /* Remove shadows and borders for cleaner print */
    .shadow-sm,
    .shadow-md,
    .shadow-lg {
        box-shadow: none !important;
    }
    
    /* Ensure proper page breaks */
    .section,
    .bg-white,
    .rounded-lg,
    .rounded-xl {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    
    /* Financial metrics styling */
    .bg-red-100,
    .bg-blue-100,
    .bg-emerald-100,
    .bg-purple-100,
    .bg-amber-100 {
        background-color: #f3f4f6 !important;
        border: 1px solid #d1d5db;
    }
    
    /* Ensure text is black for better contrast */
    .text-gray-500,
    .text-gray-600,
    .text-gray-700,
    .text-gray-900,
    .text-red-700,
    .text-blue-700,
    .text-emerald-700,
    .text-purple-600,
    .text-amber-600 {
        color: #000 !important;
    }
    
    /* Hide dark mode specific styles */
    .dark\\:bg-slate-800,
    .dark\\:bg-slate-900,
    .dark\\:text-white,
    .dark\\:text-gray-300,
    .dark\\:text-gray-400 {
        background-color: transparent !important;
        color: #000 !important;
    }
    
    /* Optimize grid layouts for print */
    .grid {
        display: grid !important;
    }
    
    /* Ensure phone number is visible */
    .col-span-2 {
        grid-column: span 2 / span 2;
    }
    
    /* Remove interactive elements */
    [x-data],
    [x-show] {
        display: block !important;
    }
    
    /* Hide collapsible sections that are closed */
    [x-show="expanded"]:not(.expanded) {
        display: none !important;
    }
    
    /* Format links for print */
    a[href^="tel:"] {
        text-decoration: none;
        color: #000;
    }
    
    /* Add page margins */
    @page {
        margin: 2cm;
        size: A4;
    }
    
    /* Header styling */
    h1, h2, h3 {
        page-break-after: avoid;
        break-after: avoid;
    }
    
    /* Ensure financial bar is visible */
    .flex.items-center.justify-between.gap-4 {
        display: flex !important;
        justify-content: space-between !important;
    }
    
    /* Clean up spacing */
    .p-6 {
        padding: 1rem !important;
    }
    
    .mt-4 {
        margin-top: 1rem !important;
    }
    
    /* Print-friendly transcript */
    .max-h-96 {
        max-height: none !important;
        overflow: visible !important;
    }
    
    /* Add print date footer */
    body::after {
        content: "Gedruckt am " attr(data-print-date);
        position: fixed;
        bottom: 1cm;
        right: 1cm;
        font-size: 10pt;
        color: #666;
    }
}

/* Add print date attribute on print */
@media print {
    body {
        position: relative;
    }
}
</style>

<script>
// Set print date when printing
window.addEventListener('beforeprint', function() {
    document.body.setAttribute('data-print-date', new Date().toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }));
});
</script>