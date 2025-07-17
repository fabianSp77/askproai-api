<div class="w-full" x-data="callHeader()">
    @php
        use Carbon\Carbon;
        use App\Models\BillingRate;
        use App\Services\ExchangeRateService;
        use App\Services\TranslationService;
        use App\Helpers\AutoTranslateHelper;
        
        $record = $getRecord();
        
        // Extract customer info
        $customerName = $record->customer?->name ?? 
                       $record->extracted_name ?? 
                       'Unbekannter Anrufer';
        $customerCompany = $record->customer?->company_name ?? '';
        $customerInitial = strtoupper(substr($customerName, 0, 1));
        $isNewCustomer = $record->first_visit ?? false;
        $customerCallCount = $record->customer?->calls()->count() ?? 1;
        
        // Sentiment
        $sentiment = $record->webhook_data['call_analysis']['user_sentiment'] ?? 
                     ($record->mlPrediction?->sentiment_label ?? 'neutral');
        $sentimentConfig = match(strtolower($sentiment)) {
            'positive' => ['color' => 'emerald', 'icon' => '😊', 'label' => 'Positiv'],
            'negative' => ['color' => 'rose', 'icon' => '😞', 'label' => 'Negativ'],
            'mixed' => ['color' => 'amber', 'icon' => '🤔', 'label' => 'Gemischt'],
            default => ['color' => 'slate', 'icon' => '😐', 'label' => 'Neutral']
        };
        
        // Call Summary
        $summary = $record->webhook_data['call_analysis']['call_summary'] ?? 
                  $record->call_summary ?? 
                  $record->summary ?? 
                  null;
        
        // Language detection
        $detectedLanguage = $record->detected_language;
        if (!$detectedLanguage && $summary) {
            $translator = app(TranslationService::class);
            $detectedLanguage = $translator->detectLanguage($summary);
        }
        
        $languageConfig = match($detectedLanguage) {
            'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch'],
            'en' => ['flag' => '🇬🇧', 'name' => 'English'],
            'fr' => ['flag' => '🇫🇷', 'name' => 'Français'],
            'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
            'it' => ['flag' => '🇮🇹', 'name' => 'Italiano'],
            'tr' => ['flag' => '🇹🇷', 'name' => 'Türkçe'],
            default => ['flag' => '🌍', 'name' => 'Auto']
        };
        
        // Get toggleable summary if available
        $toggleData = null;
        if ($summary && auth()->user()?->auto_translate_content) {
            $toggleData = AutoTranslateHelper::getToggleableContent($summary, $detectedLanguage);
        }
        
        // Call data
        $timestamp = $record->start_timestamp 
            ? Carbon::parse($record->start_timestamp)
            : $record->created_at;
        
        $durationSec = $record->duration_sec;
        $minutes = floor($durationSec / 60);
        $seconds = $durationSec % 60;
        $durationFormatted = sprintf('%02d:%02d', $minutes, $seconds);
        $durationPercent = min(100, ($durationSec / 300) * 100); // 5 min = 100%
        
        // Status
        $statusConfig = match($record->status ?? 'ended') {
            'completed', 'ended' => ['color' => 'emerald', 'label' => 'Beendet', 'icon' => 'check-circle'],
            'in_progress' => ['color' => 'amber', 'label' => 'Laufend', 'icon' => 'arrow-path'],
            'failed' => ['color' => 'rose', 'label' => 'Fehlgeschlagen', 'icon' => 'x-circle'],
            default => ['color' => 'slate', 'label' => 'Unbekannt', 'icon' => 'question-mark-circle']
        };
        
        // Company/Branch
        $companyName = $record->company?->name ?? 'Unbekannt';
        $branchName = $record->branch?->name ?? 'Hauptfiliale';
        
        // Cost calculations
        $callCostCents = 0;
        if (isset($record->webhook_data['call_cost'])) {
            if (is_array($record->webhook_data['call_cost'])) {
                $callCostCents = $record->webhook_data['call_cost']['combined_cost'] ?? 
                                $record->webhook_data['call_cost']['total_cost'] ?? 
                                $record->webhook_data['call_cost']['total'] ?? 0;
            } else {
                $callCostCents = floatval($record->webhook_data['call_cost']);
            }
        }
        
        $callCostEUR = ExchangeRateService::convertCentsToEur($callCostCents);
        
        // Revenue calculation
        $billingRate = $record->company?->billingRate;
        $companyRate = $billingRate ? $billingRate->rate_per_minute : BillingRate::getDefaultRate();
        
        if ($billingRate) {
            $revenue = $billingRate->calculateCharge($record->duration_sec);
        } else {
            $revenue = ($record->duration_sec / 60) * $companyRate;
        }
        
        $profit = $revenue - $callCostEUR;
        $margin = $revenue > 0 ? ($profit / $revenue * 100) : 0;
        $profitPercent = $revenue > 0 ? min(100, max(0, $margin)) : 0;
    @endphp

    {{-- Modern Hero Section --}}
    <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 rounded-xl shadow-sm overflow-hidden">
        {{-- Customer Header --}}
        <div class="p-6 pb-4">
            <div class="flex items-start justify-between gap-4">
                {{-- Customer Info --}}
                <div class="flex items-start gap-4 min-w-0 flex-1">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-{{ $sentimentConfig['color'] }}-400 to-{{ $sentimentConfig['color'] }}-600 flex items-center justify-center text-white font-semibold text-lg shadow-md">
                        {{ $customerInitial }}
                    </div>
                    
                    {{-- Name & Details --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white truncate">
                                {{ $customerName }}
                            </h2>
                            @if($isNewCustomer)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                    </svg>
                                    Neukunde
                                </span>
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $customerCallCount }} {{ $customerCallCount === 1 ? 'Anruf' : 'Anrufe' }}
                                </span>
                            @endif
                        </div>
                        @if($customerCompany)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                {{ $customerCompany }}
                            </p>
                        @endif
                    </div>
                </div>
                
                {{-- Sentiment Badge --}}
                <div class="flex-shrink-0">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-{{ $sentimentConfig['color'] }}-100 dark:bg-{{ $sentimentConfig['color'] }}-900/30 text-{{ $sentimentConfig['color'] }}-700 dark:text-{{ $sentimentConfig['color'] }}-400 font-medium text-sm">
                        <span class="text-lg">{{ $sentimentConfig['icon'] }}</span>
                        <span>{{ $sentimentConfig['label'] }}</span>
                    </div>
                </div>
            </div>
            
            {{-- Summary Integration --}}
            @if($summary)
                <div class="mt-4 -mx-2 px-4 py-3 bg-white/50 dark:bg-slate-800/50 rounded-lg backdrop-blur-sm"
                     x-data="{ 
                        showOriginal: false,
                        originalText: @js($summary),
                        translatedText: @js($toggleData && $toggleData['should_translate'] ? $toggleData['translated'] : $summary),
                        sourceLanguage: @js($detectedLanguage ?? 'unknown'),
                        targetLanguage: @js(auth()->user()->content_language ?? 'de'),
                        shouldTranslate: @js($toggleData && $toggleData['should_translate'])
                     }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" 
                               x-text="showOriginal ? originalText : translatedText">
                            </p>
                            @if($toggleData && $toggleData['should_translate'])
                                <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                                        </path>
                                    </svg>
                                    <span x-show="!showOriginal">
                                        Übersetzt: <strong x-text="sourceLanguage.toUpperCase()"></strong> → <strong x-text="targetLanguage.toUpperCase()"></strong>
                                    </span>
                                    <span x-show="showOriginal" style="display: none;">
                                        Original in <strong x-text="sourceLanguage.toUpperCase()"></strong>
                                    </span>
                                </div>
                            @endif
                        </div>
                        @if($toggleData && $toggleData['should_translate'])
                            <button 
                                @click="showOriginal = !showOriginal"
                                type="button"
                                class="flex-shrink-0 p-1.5 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-slate-700 transition-colors"
                                title="Original anzeigen / Übersetzung anzeigen"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                                    </path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Stats Bar --}}
        <div class="px-6 pb-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                {{-- Phone Number --}}
                <div class="group cursor-pointer" @click="copyToClipboard('{{ $record->from_number }}')">
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm group-hover:shadow-md transition-all duration-200">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Telefon</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $record->from_number }}</p>
                            </div>
                            <svg class="w-3 h-3 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Duration with Progress --}}
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Dauer</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $durationFormatted }}</p>
                            <div class="mt-1 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                <div class="bg-purple-500 h-1 rounded-full transition-all duration-300" style="width: {{ $durationPercent }}%"></div>
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
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $timestamp->format('d.m.Y') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $timestamp->format('H:i') }} Uhr</p>
                        </div>
                    </div>
                </div>
                
                {{-- Language & Status --}}
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-{{ $statusConfig['color'] }}-100 dark:bg-{{ $statusConfig['color'] }}-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-{{ $statusConfig['color'] }}-600 dark:text-{{ $statusConfig['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    
    {{-- Financial Metrics Card --}}
    <div class="mt-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Finanzdaten
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Costs --}}
            <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-4">
                <div class="relative z-10">
                    <p class="text-xs font-medium text-red-700 dark:text-red-400 uppercase tracking-wider">Kosten</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-400 mt-1">{{ number_format($callCostEUR, 2) }}€</p>
                    <p class="text-xs text-red-600 dark:text-red-500 mt-1">Retell.ai Gebühr</p>
                </div>
                <svg class="absolute right-0 bottom-0 w-20 h-20 text-red-200 dark:text-red-900/30 transform translate-x-8 translate-y-8" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            
            {{-- Revenue --}}
            <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4">
                <div class="relative z-10">
                    <p class="text-xs font-medium text-blue-700 dark:text-blue-400 uppercase tracking-wider">Umsatz</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-400 mt-1">{{ number_format($revenue, 2) }}€</p>
                    <p class="text-xs text-blue-600 dark:text-blue-500 mt-1">{{ number_format($companyRate, 2) }}€/Min</p>
                </div>
                <svg class="absolute right-0 bottom-0 w-20 h-20 text-blue-200 dark:text-blue-900/30 transform translate-x-8 translate-y-8" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                </svg>
            </div>
            
            {{-- Profit with Progress --}}
            <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-{{ $profit >= 0 ? 'emerald' : 'gray' }}-50 to-{{ $profit >= 0 ? 'emerald' : 'gray' }}-100 dark:from-{{ $profit >= 0 ? 'emerald' : 'gray' }}-900/20 dark:to-{{ $profit >= 0 ? 'emerald' : 'gray' }}-800/20 p-4">
                <div class="relative z-10">
                    <p class="text-xs font-medium text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-700 dark:text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-400 uppercase tracking-wider">Gewinn</p>
                    <p class="text-2xl font-bold text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-700 dark:text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-400 mt-1">
                        {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 2) }}€
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="flex-1 bg-{{ $profit >= 0 ? 'emerald' : 'gray' }}-200 dark:bg-{{ $profit >= 0 ? 'emerald' : 'gray' }}-700 rounded-full h-2">
                            <div class="bg-{{ $profit >= 0 ? 'emerald' : 'gray' }}-500 h-2 rounded-full transition-all duration-300" style="width: {{ $profitPercent }}%"></div>
                        </div>
                        <span class="text-xs font-medium text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-700 dark:text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-400">{{ number_format($margin, 0) }}%</span>
                    </div>
                </div>
                <svg class="absolute right-0 bottom-0 w-20 h-20 text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-200 dark:text-{{ $profit >= 0 ? 'emerald' : 'gray' }}-900/30 transform translate-x-8 translate-y-8" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
        
        {{-- Company & Branch Info --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span>{{ $companyName }} • {{ $branchName }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Details anzeigen">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Notification for copy --}}
    <div x-show="showCopied" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg text-sm"
         style="display: none;">
        Kopiert!
    </div>
</div>

<script>
function callHeader() {
    return {
        showCopied: false,
        
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showCopied = true;
                setTimeout(() => {
                    this.showCopied = false;
                }, 2000);
            });
        }
    }
}
</script>