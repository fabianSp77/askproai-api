@php
    use Carbon\Carbon;
    use App\Models\BillingRate;
    use App\Services\ExchangeRateService;
    use App\Services\TranslationService;
    use App\Helpers\AutoTranslateHelper;
    
    $record = $getRecord();
    
    // Core data extraction
    $customerName = $record->customer?->name ?? $record->extracted_name ?? 'Unbekannter Anrufer';
    $companyName = $record->company?->name ?? 'Unbekannte Firma';
    $phone = $record->from_number;
    $duration = $record->duration_sec;
    $timestamp = $record->start_timestamp ? Carbon::parse($record->start_timestamp) : $record->created_at;
    
    // Financial data
    $financials = app(\App\Services\CallFinancialService::class)->calculateMetrics($record);
    
    // Summary & Translation
    $summary = $record->webhook_data['call_analysis']['call_summary'] ?? 
              $record->call_summary ?? 
              $record->summary ?? 
              null;
              
    // Auto-translate handling
    $translator = app(TranslationService::class);
    $detectedLanguage = $record->detected_language ?? ($summary ? $translator->detectLanguage($summary) : null);
    $userLanguage = auth()->user()->content_language ?? 'de';
    
    $toggleData = null;
    $showToggle = false;
    $displayText = $summary;
    
    if ($summary) {
        $toggleData = AutoTranslateHelper::getToggleableContent($summary, $detectedLanguage);
        $showToggle = $toggleData['should_translate'] && 
                      auth()->user()?->auto_translate_content &&
                      $detectedLanguage !== $userLanguage;
        $displayText = $showToggle ? $toggleData['translated'] : $summary;
    }
              
    // Sentiment
    $sentimentType = strtolower($record->webhook_data['call_analysis']['user_sentiment'] ?? 
                              $record->mlPrediction?->sentiment_label ?? 'neutral');
                              
    // Portal URL generation
    $portalUrl = '#';
    if (auth()->user() && auth()->user()->hasRole('Super Admin')) {
        $token = \Str::random(64);
        cache()->put('admin_portal_access_' . $token, [
            'admin_id' => auth()->id(),
            'company_id' => $record->company_id,
            'redirect_to' => route('business.calls.show', $record->id),
        ], 300);
        $portalUrl = route('business.admin.access', [
            'token' => $token, 
            'redirect' => urlencode(route('business.calls.show', $record->id))
        ]);
    }
@endphp

<div class="w-full" x-data="callHeaderModern()">
    {{-- Compact Header --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm">
        {{-- Title Bar --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    {{-- Call Status Indicator --}}
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $sentimentType === 'positive' ? 'bg-green-100 dark:bg-green-900/20' : ($sentimentType === 'negative' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-800') }}">
                            <svg class="w-5 h-5 {{ $sentimentType === 'positive' ? 'text-green-600 dark:text-green-400' : ($sentimentType === 'negative' ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        @if($record->status === 'in_progress')
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        @endif
                    </div>
                    
                    {{-- Call Info --}}
                    <div>
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $customerName }} → {{ $companyName }}
                        </h1>
                        <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $timestamp->format('d.m.Y H:i') }}</span>
                            <span>•</span>
                            <span>{{ sprintf('%02d:%02d', floor($duration / 60), $duration % 60) }}</span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $phone }}
                                <button @click="copyPhone('{{ $phone }}')" 
                                        class="p-0.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors"
                                        title="Kopieren">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    {{-- Non-Billable Badge --}}
                    @if($record->metadata['non_billable'] ?? false)
                        <div class="non-billable-badge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Nicht abrechenbar</span>
                            @if($record->metadata['non_billable_reason'] ?? false)
                                <span class="text-xs opacity-75">({{ $record->metadata['non_billable_reason'] }})</span>
                            @endif
                        </div>
                    @endif
                    
                    <a href="{{ $portalUrl }}" 
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 rounded-md transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Business Portal
                    </a>
                    
                    <div class="flex items-center gap-1 border-l border-gray-200 dark:border-gray-700 pl-2">
                        <button @click="window.print()" 
                                class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors"
                                title="Drucken">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                        </button>
                        <button @click="exportPDF()" 
                                class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors"
                                title="PDF Export">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Financial Bar (Subtle) --}}
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4 text-xs">
                <span class="text-gray-500 dark:text-gray-400">
                    Kosten: <strong class="text-gray-900 dark:text-white">{{ number_format($financials['cost'], 2) }}€</strong>
                </span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <span class="text-gray-500 dark:text-gray-400">
                    Umsatz: <strong class="text-gray-900 dark:text-white">{{ number_format($financials['revenue'], 2) }}€</strong>
                </span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <span class="text-gray-500 dark:text-gray-400">
                    Gewinn: <strong class="{{ $financials['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $financials['profit'] >= 0 ? '+' : '' }}{{ number_format($financials['profit'], 2) }}€
                    </strong>
                    <span class="text-gray-400">({{ number_format($financials['margin'], 0) }}%)</span>
                </span>
            </div>
        </div>
        
        {{-- Summary Section (if exists) --}}
        @if($summary)
            <div class="px-4 py-3" x-data="{ 
                showOriginal: false,
                originalText: @js($summary),
                translatedText: @js($showToggle ? $toggleData['translated'] : $summary),
                sourceLanguage: @js($detectedLanguage ?? 'unknown'),
                targetLanguage: @js($userLanguage ?? 'de'),
                isTranslated: @js($showToggle)
            }">
                <div class="flex-1">
                    {{-- Main text display --}}
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" id="summary-text">
                        <span x-show="!showOriginal && isTranslated" x-text="translatedText"></span>
                        <span x-show="showOriginal || !isTranslated" x-text="originalText"></span>
                    </p>
                    
                    {{-- Toggle controls --}}
                    @if($showToggle)
                        <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
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
                                       text-gray-700 bg-white border border-gray-300 hover:bg-gray-50
                                       dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
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
            </div>
        @else
            <div class="px-4 py-3">
                <div class="flex items-start gap-3">
                    <div class="flex-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                            Keine Zusammenfassung verfügbar
                        </p>
                    </div>
                    <button @click="translateSummary()" style="display: none;" 
                            class="flex-shrink-0 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors"
                            title="Übersetzen">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Quick Actions Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
        {{-- Customer Info Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Kunde</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $customerName }}</p>
                    @if($record->customer)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $record->customer->calls()->count() }} Anrufe
                        </p>
                    @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400 mt-1">
                            Neukunde
                        </span>
                    @endif
                </div>
                <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                        {{ strtoupper(substr($customerName, 0, 1)) }}
                    </span>
                </div>
            </div>
        </div>
        
        {{-- Appointment Status Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Termin</p>
                    @if($record->appointment)
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Gebucht</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $record->appointment->starts_at->format('d.m.Y H:i') }}
                        </p>
                    @elseif($record->appointment_requested)
                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Angefragt</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Wartet auf Bestätigung</p>
                    @else
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kein Termin</p>
                    @endif
                </div>
                <div class="w-8 h-8 rounded-full {{ $record->appointment ? 'bg-green-100 dark:bg-green-900/20' : 'bg-gray-100 dark:bg-gray-800' }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $record->appointment ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        {{-- Sentiment Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Stimmung</p>
                    <p class="text-sm font-medium {{ $sentimentType === 'positive' ? 'text-green-600 dark:text-green-400' : ($sentimentType === 'negative' ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}">
                        {{ ucfirst($sentimentType) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $sentimentType === 'positive' ? 'Zufriedener Kunde' : ($sentimentType === 'negative' ? 'Unzufrieden' : 'Neutral') }}
                    </p>
                </div>
                <div class="text-2xl">
                    {{ $sentimentType === 'positive' ? '😊' : ($sentimentType === 'negative' ? '😞' : '😐') }}
                </div>
            </div>
        </div>
        
        {{-- Actions Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Schnellaktionen</p>
            <div class="space-y-2">
                <a href="tel:{{ $phone }}" 
                   class="flex items-center gap-2 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Zurückrufen
                </a>
                @if($record->customer)
                    <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', [$record->customer]) }}" 
                       class="flex items-center gap-2 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Kundenprofil
                    </a>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Toast Notification --}}
    <div x-show="showToast" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg text-sm"
         style="display: none;">
        <span x-text="toastMessage"></span>
    </div>
</div>

<script>
function callHeaderModern() {
    return {
        showToast: false,
        toastMessage: '',
        
        copyPhone(phone) {
            navigator.clipboard.writeText(phone).then(() => {
                this.showToastMessage('Nummer kopiert!');
            }).catch(() => {
                // Fallback
                const input = document.createElement('input');
                input.value = phone;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                this.showToastMessage('Nummer kopiert!');
            });
        },
        
        showToastMessage(message) {
            this.toastMessage = message;
            this.showToast = true;
            setTimeout(() => {
                this.showToast = false;
            }, 2000);
        },
        
        translateSummary() {
            const summaryEl = document.getElementById('summary-text');
            const originalText = summaryEl.textContent;
            
            this.showToastMessage('Übersetze...');
            
            // Get call ID from URL
            const callId = window.location.pathname.split('/').pop();
            
            fetch(`{{ route('admin.api.calls.translate-summary', ':id') }}`.replace(':id', callId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    target_language: '{{ auth()->user()->content_language ?? "de" }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                summaryEl.textContent = data.translated;
                this.showToastMessage('Übersetzt!');
                
                // Add indicator
                const indicator = document.createElement('span');
                indicator.className = 'text-xs text-gray-500 dark:text-gray-400 ml-2';
                indicator.textContent = `(Übersetzt aus ${data.source_language.toUpperCase()})`;
                summaryEl.appendChild(indicator);
            })
            .catch(() => {
                this.showToastMessage('Übersetzung fehlgeschlagen');
            });
        },
        
        exportPDF() {
            const callId = window.location.pathname.split('/').pop();
            window.open(`/api/calls/${callId}/export-pdf`, '_blank');
            this.showToastMessage('PDF wird erstellt...');
        }
    }
}
</script>

<style>
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

@media print {
    button, a[target="_blank"], .no-print {
        display: none !important;
    }
    
    .shadow-sm, .hover\\:shadow-md {
        box-shadow: none !important;
    }
    
    .bg-gray-50, .dark\\:bg-gray-800\\/50 {
        background: #f9fafb !important;
    }
    
    .text-gray-500, .dark\\:text-gray-400 {
        color: #6b7280 !important;
    }
}
</style>