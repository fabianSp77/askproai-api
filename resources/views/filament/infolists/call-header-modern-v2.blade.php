@php
    use Carbon\Carbon;
    use App\Models\BillingRate;
    use App\Services\ExchangeRateService;
    use App\Services\TranslationService;
    use App\Helpers\AutoTranslateHelper;
    
    $record = $getRecord();
    $customer = $record->customer;
    
    // Core data extraction
    $customerName = $customer?->name ?? $record->extracted_name ?? 'Unbekannter Anrufer';
    $companyName = $record->company?->name ?? 'Unbekannte Firma';
    $phone = $record->from_number;
    $email = $customer?->email;
    $duration = $record->duration_sec;
    $timestamp = $record->start_timestamp ? Carbon::parse($record->start_timestamp) : $record->created_at;
    
    // Customer info
    $nameParts = explode(' ', $customerName);
    $initials = '';
    if (count($nameParts) >= 2) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $initials = strtoupper(substr($customerName, 0, 2));
    }
    
    $totalCalls = $customer ? $customer->calls()->count() : 1;
    $isNewCustomer = !$customer || $customer->created_at->diffInDays(now()) < 30;
    
    // Financial data
    $financials = app(\App\Services\CallFinancialService::class)->calculateMetrics($record);
    
    // Summary & Translation
    $summary = $record->webhook_data['call_analysis']['call_summary'] ?? 
              $record->call_summary ?? 
              $record->summary ?? 
              null;
              
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

<div class="w-full" x-data="callHeaderModernV2()">
    {{-- Modern Header with Customer Focus --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Customer Bar --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-850 px-6 py-4">
            <div class="flex items-center justify-between">
                {{-- Customer Info Section --}}
                <div class="flex items-center gap-4">
                    {{-- Avatar --}}
                    <div class="relative">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-lg shadow-md">
                            {{ $initials }}
                        </div>
                        @if($isNewCustomer)
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-xs">N</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Name and Contact --}}
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            {{ $customerName }}
                            @if($isNewCustomer)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Neukunde
                                </span>
                            @endif
                        </h2>
                        <div class="flex items-center gap-3 mt-1 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $phone }}
                            </span>
                            @if($email)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $email }}
                                </span>
                            @endif
                            @if($customer)
                                <span>•</span>
                                <span>{{ $totalCalls }} {{ $totalCalls === 1 ? 'Anruf' : 'Anrufe' }} gesamt</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Quick Actions --}}
                <div class="flex items-center gap-2">
                    {{-- Call Button --}}
                    <a href="tel:{{ $phone }}" 
                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-green-500 hover:bg-green-600 text-white shadow hover:shadow-md transform hover:scale-105 transition-all duration-200"
                       title="Anrufen">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </a>
                    
                    {{-- Email Button --}}
                    @if($email)
                        <a href="mailto:{{ $email }}" 
                           class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-500 hover:bg-blue-600 text-white shadow hover:shadow-md transform hover:scale-105 transition-all duration-200"
                           title="E-Mail senden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </a>
                    @endif
                    
                    {{-- More Actions --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-colors duration-200"
                                title="Weitere Aktionen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                @if($customer)
                                    <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', [$customer]) }}"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Kundenprofil
                                    </a>
                                @endif
                                <button @click="copyToClipboard('{{ $phone }}')"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Nummer kopieren
                                </button>
                                <hr class="my-1 border-gray-200 dark:border-gray-700">
                                <a href="{{ $portalUrl }}" 
                                   target="_blank"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Business Portal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Call Information Bar --}}
        <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                {{-- Call Details --}}
                <div class="flex items-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $sentimentType === 'positive' ? 'bg-green-100 dark:bg-green-900/20' : ($sentimentType === 'negative' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-800') }}">
                            <svg class="w-5 h-5 {{ $sentimentType === 'positive' ? 'text-green-600 dark:text-green-400' : ($sentimentType === 'negative' ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                Anruf an {{ $companyName }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $timestamp->format('d.m.Y H:i') }} • {{ sprintf('%02d:%02d', floor($duration / 60), $duration % 60) }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- Financial Info --}}
                    <div class="flex items-center gap-4 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">
                            Kosten: <strong class="text-gray-900 dark:text-white">{{ number_format($financials['cost'], 2) }}€</strong>
                        </span>
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        <span class="text-gray-500 dark:text-gray-400">
                            Gewinn: <strong class="{{ $financials['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $financials['profit'] >= 0 ? '+' : '' }}{{ number_format($financials['profit'], 2) }}€
                            </strong>
                        </span>
                    </div>
                </div>
                
                {{-- Status Badges --}}
                <div class="flex items-center gap-2">
                    @if($record->metadata['non_billable'] ?? false)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Nicht abrechenbar
                        </span>
                    @endif
                    
                    @if($record->appointment_requested)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Termin angefragt
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Summary Section (if exists) --}}
        @if($summary)
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    {{ Str::limit($summary, 200) }}
                </p>
            </div>
        @endif
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
function callHeaderModernV2() {
    return {
        showToast: false,
        toastMessage: '',
        
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToastMessage('Kopiert!');
            }).catch(() => {
                // Fallback
                const input = document.createElement('input');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                this.showToastMessage('Kopiert!');
            });
        },
        
        showToastMessage(message) {
            this.toastMessage = message;
            this.showToast = true;
            setTimeout(() => {
                this.showToast = false;
            }, 2000);
        }
    }
}
</script>