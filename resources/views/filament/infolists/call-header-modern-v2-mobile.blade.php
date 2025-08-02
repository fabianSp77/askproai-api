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
    
    // Summary
    $summary = $record->webhook_data['call_analysis']['call_summary'] ?? 
              $record->call_summary ?? 
              $record->summary ?? 
              null;
              
    // Sentiment
    $sentimentType = strtolower($record->webhook_data['call_analysis']['user_sentiment'] ?? 
                              $record->mlPrediction?->sentiment_label ?? 'neutral');
@endphp

<div class="w-full">
    {{-- Mobile-Optimized Header --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Customer Section - Stack on Mobile --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-850 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                {{-- Customer Info --}}
                <div class="flex items-center gap-3">
                    {{-- Avatar --}}
                    <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-base sm:text-lg shadow-md">
                            {{ $initials }}
                        </div>
                        @if($isNewCustomer)
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 bg-green-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-[10px] sm:text-xs">N</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Name and Contact --}}
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white truncate">
                            {{ $customerName }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $phone }}
                            </span>
                            @if($customer)
                                <span>{{ $totalCalls }} {{ $totalCalls === 1 ? 'Anruf' : 'Anrufe' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Quick Actions - Horizontal on Mobile --}}
                <div class="flex items-center gap-2 self-start sm:self-center">
                    {{-- Call Button --}}
                    <a href="tel:{{ $phone }}" 
                       class="inline-flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-green-500 hover:bg-green-600 text-white shadow hover:shadow-md transition-all duration-200"
                       title="Anrufen">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </a>
                    
                    {{-- Email Button --}}
                    @if($email)
                        <a href="mailto:{{ $email }}" 
                           class="inline-flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-blue-500 hover:bg-blue-600 text-white shadow hover:shadow-md transition-all duration-200"
                           title="E-Mail senden">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </a>
                    @endif
                    
                    {{-- More Actions - Simplified for Mobile --}}
                    @if($customer)
                        <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', [$customer]) }}"
                           class="inline-flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-colors duration-200"
                           title="Kundenprofil">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
            
            {{-- Status Badges - Below on Mobile --}}
            @if($isNewCustomer)
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                        Neukunde
                    </span>
                </div>
            @endif
        </div>
        
        {{-- Call Information - Simplified for Mobile --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                {{-- Call Details --}}
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center {{ $sentimentType === 'positive' ? 'bg-green-100 dark:bg-green-900/20' : ($sentimentType === 'negative' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-800') }}">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 {{ $sentimentType === 'positive' ? 'text-green-600 dark:text-green-400' : ($sentimentType === 'negative' ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $timestamp->format('d.m.Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ sprintf('%02d:%02d', floor($duration / 60), $duration % 60) }} • {{ number_format($financials['cost'], 2) }}€
                        </p>
                    </div>
                </div>
                
                {{-- Status Badges --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if($record->metadata['non_billable'] ?? false)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Nicht abrechenbar
                        </span>
                    @endif
                    
                    @if($record->appointment_requested)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                            Termin angefragt
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Summary Section - Full Width on Mobile --}}
        @if($summary)
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    {{ $summary }}
                </p>
            </div>
        @endif
    </div>
</div>