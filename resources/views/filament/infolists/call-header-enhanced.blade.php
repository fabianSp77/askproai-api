<div class="w-full space-y-6">
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
        
        // Sentiment
        $sentiment = $record->webhook_data['call_analysis']['user_sentiment'] ?? 
                     ($record->mlPrediction?->sentiment_label ?? 'Unknown');
        $sentimentColor = match(strtolower($sentiment)) {
            'positive' => 'text-green-600',
            'negative' => 'text-red-600',
            'neutral' => 'text-gray-600',
            default => 'text-gray-600'
        };
        $sentimentLabel = match(strtolower($sentiment)) {
            'positive' => '😊 Positiv',
            'negative' => '😞 Negativ',
            'neutral' => '😐 Neutral',
            'mixed' => '🤔 Gemischt',
            default => ucfirst($sentiment)
        };
        
        // Call Summary
        $summary = $record->webhook_data['call_analysis']['call_summary'] ?? 
                  $record->call_summary ?? 
                  $record->summary ?? 
                  null;
        
        // Language detection
        $detectedLanguage = $record->detected_language;
        if (!$detectedLanguage && $summary) {
            // Try to detect from summary
            $translator = app(TranslationService::class);
            $detectedLanguage = $translator->detectLanguage($summary);
        }
        if (!$detectedLanguage && $record->transcript) {
            // Try to detect from transcript
            $translator = app(TranslationService::class);
            $detectedLanguage = $translator->detectLanguage($record->transcript);
        }
        
        $languageLabel = match($detectedLanguage) {
            'de' => '🇩🇪 Deutsch',
            'en' => '🇬🇧 English',
            'fr' => '🇫🇷 Français',
            'es' => '🇪🇸 Español',
            'it' => '🇮🇹 Italiano',
            'tr' => '🇹🇷 Türkçe',
            default => $detectedLanguage ? strtoupper($detectedLanguage) : '🌍 Automatisch erkennen'
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
        
        // Status
        $status = $record->status ?? 'ended';
        $statusColor = match($status) {
            'completed', 'ended' => 'success',
            'in_progress' => 'warning',
            'failed' => 'danger',
            default => 'gray'
        };
        $statusLabel = match($status) {
            'completed', 'ended' => 'Beendet',
            'in_progress' => 'Laufend',
            'failed' => 'Fehlgeschlagen',
            default => ucfirst($status)
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
        
        $callCostUSD = $callCostCents / 100;
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
    @endphp

    {{-- Customer Info with Sentiment --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $customerName }}
                </h2>
                @if($customerCompany)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $customerCompany }}
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium {{ $sentimentColor }} bg-{{ str_contains($sentimentColor, 'green') ? 'green' : (str_contains($sentimentColor, 'red') ? 'red' : 'gray') }}-100 dark:bg-{{ str_contains($sentimentColor, 'green') ? 'green' : (str_contains($sentimentColor, 'red') ? 'red' : 'gray') }}-900/20">
                    {{ $sentimentLabel }}
                </span>
            </div>
        </div>
    </div>

    {{-- Call Summary (if available) --}}
    @if($summary)
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4" 
             x-data="{ 
                showOriginal: false,
                originalText: @js($summary),
                translatedText: @js($toggleData && $toggleData['should_translate'] ? $toggleData['translated'] : $summary),
                sourceLanguage: @js($detectedLanguage ?? 'unknown'),
                targetLanguage: @js(auth()->user()->content_language ?? 'de'),
                shouldTranslate: @js($toggleData && $toggleData['should_translate'])
             }">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                Zusammenfassung des Anrufs
            </h3>
            <div class="prose prose-sm max-w-none text-blue-800 dark:text-blue-200">
                <p x-show="!showOriginal" x-text="translatedText"></p>
                <p x-show="showOriginal" x-text="originalText" style="display: none;"></p>
            </div>
            
            @if($toggleData && $toggleData['should_translate'])
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-2 text-xs text-blue-700 dark:text-blue-300">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                            </path>
                        </svg>
                        <span x-show="!showOriginal">
                            Automatisch übersetzt von <strong x-text="sourceLanguage.toUpperCase()"></strong> 
                            nach <strong x-text="targetLanguage.toUpperCase()"></strong>
                        </span>
                        <span x-show="showOriginal" style="display: none;">
                            Originaltext in <strong x-text="sourceLanguage.toUpperCase()"></strong>
                        </span>
                    </div>
                    
                    <button 
                        @click="showOriginal = !showOriginal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md
                               text-blue-700 bg-white border border-blue-300 hover:bg-blue-50
                               dark:text-blue-200 dark:bg-blue-800 dark:border-blue-600 dark:hover:bg-blue-700
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                               transition-colors duration-200"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                            </path>
                        </svg>
                        <span x-show="!showOriginal">Original anzeigen</span>
                        <span x-show="showOriginal" style="display: none;">Übersetzung anzeigen</span>
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Call Data Grid --}}
    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Anrufdaten</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Phone Number (left) --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Telefonnummer
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-m-phone class="w-4 h-4 text-gray-400" />
                    <span class="font-mono text-sm">{{ $record->from_number }}</span>
                </div>
            </div>
            
            {{-- Duration --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Dauer
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-m-clock class="w-4 h-4 text-gray-400" />
                    <span class="font-mono text-sm">{{ $durationFormatted }}</span>
                    <span class="text-xs text-gray-500">({{ $durationSec }} Sek)</span>
                </div>
            </div>
            
            {{-- Date & Time --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Zeitpunkt
                </div>
                <div>
                    <div class="text-sm font-medium">{{ $timestamp->format('d.m.Y') }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ $timestamp->format('H:i:s') }} Uhr</div>
                </div>
            </div>
            
            {{-- Language --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Sprache
                </div>
                <div class="text-sm font-medium">{{ $languageLabel }}</div>
            </div>
            
            {{-- Status --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Status
                </div>
                <x-filament::badge :color="$statusColor" size="sm">
                    {{ $statusLabel }}
                </x-filament::badge>
            </div>
            
            {{-- Company/Branch --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Unternehmen / Filiale
                </div>
                <div>
                    <div class="text-sm font-medium truncate" title="{{ $companyName }}">
                        {{ $companyName }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $branchName }}">
                        {{ $branchName }}
                    </div>
                </div>
            </div>
            
            {{-- Costs & Revenue --}}
            <div class="md:col-span-2">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Kosten / Umsatz / Gewinn
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm">
                        <span class="text-red-600 font-semibold">{{ number_format($callCostEUR, 2) }}€</span>
                        <span class="text-gray-400">/</span>
                        <span class="text-blue-600 font-semibold">{{ number_format($revenue, 2) }}€</span>
                        <span class="text-gray-400">/</span>
                        <span class="{{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                            {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 2) }}€
                        </span>
                    </span>
                    <span class="text-xs text-gray-500">({{ number_format($margin, 0) }}%)</span>
                </div>
            </div>
        </div>
    </div>
</div>