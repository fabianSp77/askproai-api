@php
    use App\Services\Customer\CustomerMatchingService;
    use App\Models\CustomerJourneyStage;
    use Carbon\Carbon;
    
    $record = $getRecord();
    $matchingService = app(CustomerMatchingService::class);
    
    // Journey Stages
    $journeyStages = CustomerJourneyStage::orderBy('order')->get();
    
    // Customer Data
    $customer = $record->customer;
    $currentStage = null;
    
    if ($customer) {
        $currentStage = $journeyStages->firstWhere('code', $customer->journey_status);
        
        // Touchpoints laden
        $touchpoints = DB::table('customer_touchpoints')
            ->where('customer_id', $customer->id)
            ->orderBy('occurred_at', 'desc')
            ->limit(10)
            ->get();
            
        // Journey Events
        $journeyEvents = DB::table('customer_journey_events')
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    } else {
        // Potenzielle Matches anzeigen
        $phoneNumber = $record->from_number;
        $companyName = $record->metadata['customer_data']['company'] ?? null;
        $customerNumber = $record->metadata['customer_data']['customer_number'] ?? null;
        
        $potentialMatches = $matchingService->findRelatedCustomers(
            $record->company_id,
            $record->to_number,
            $phoneNumber,
            $companyName,
            $customerNumber
        );
    }
@endphp

<div class="customer-journey-widget" x-data="{ 
    activeTab: 'journey',
    showAllTouchpoints: false,
    showDetails: window.innerWidth > 768 
}">
    
    @if($customer)
        {{-- Customer Journey Status Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header mit Kunde Info --}}
            <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-{{ $currentStage?->color ?? 'gray' }}-100 dark:bg-{{ $currentStage?->color ?? 'gray' }}-900/20 flex items-center justify-center">
                            @if($currentStage?->icon)
                                <x-dynamic-component :component="$currentStage->icon" class="h-6 w-6 text-{{ $currentStage->color }}-600 dark:text-{{ $currentStage->color }}-400" />
                            @else
                                <x-heroicon-o-user class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $customer->name }}
                                @if($customer->company_name)
                                    <span class="text-sm text-gray-500 dark:text-gray-400">({{ $customer->company_name }})</span>
                                @endif
                            </h3>
                            <p class="text-sm text-{{ $currentStage?->color ?? 'gray' }}-600 dark:text-{{ $currentStage?->color ?? 'gray' }}-400 font-medium">
                                {{ $currentStage?->name ?? 'Unbekannt' }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- Quick Stats - Responsive --}}
                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-4">
                        <div class="text-center">
                            <div class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $customer->call_count ?? 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Anrufe</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $customer->appointment_count ?? 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Termine</div>
                        </div>
                        @if($customer->total_revenue > 0)
                            <div class="text-center col-span-2 sm:col-span-1">
                                <div class="text-xl sm:text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($customer->total_revenue, 2) }}€</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Umsatz</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Journey Progress Bar --}}
            <div class="px-6 py-4 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 rounded-lg">
                <div class="overflow-x-auto">
                    <div class="relative" style="padding: 2rem 0 3.5rem 0; min-width: max-content;">
                        {{-- Stages Container --}}
                        <div class="relative flex items-start gap-2">
                            @foreach($journeyStages->where('order', '<=', 10) as $index => $stage)
                                @php
                                    $isPast = $currentStage && $stage->order < $currentStage->order;
                                    $isCurrent = $currentStage && $stage->code === $currentStage->code;
                                    $isFuture = $currentStage && $stage->order > $currentStage->order;
                                    $isLast = $loop->last;
                                @endphp
                                
                                <div class="relative flex flex-col items-center" style="width: 120px;">
                                    {{-- Connection Line --}}
                                    @if(!$isLast)
                                        <div class="absolute top-6 left-1/2 w-full h-0.5 -z-10" style="transform: translateX(50%);">
                                            <div class="h-full w-full {{ $isPast ? 'bg-gradient-to-r from-green-500 to-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                                        </div>
                                    @endif
                                    
                                    {{-- Stage Circle with Icon --}}
                                    <div class="relative group">
                                        <div class="relative z-20 h-12 w-12 rounded-full flex items-center justify-center transition-all duration-300 shadow-sm
                                            {{ $isCurrent ? 'bg-' . $stage->color . '-500 ring-4 ring-' . $stage->color . '-200 dark:ring-' . $stage->color . '-800 scale-110 shadow-lg' : '' }}
                                            {{ $isPast ? 'bg-green-500 hover:bg-green-600' : '' }}
                                            {{ $isFuture ? 'bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 hover:border-' . $stage->color . '-400' : '' }}">
                                            
                                            @if($isPast)
                                                <x-heroicon-s-check class="h-6 w-6 text-white" />
                                            @elseif($isCurrent)
                                                @if($stage->icon)
                                                    <x-dynamic-component :component="$stage->icon" class="h-6 w-6 text-white" />
                                                @else
                                                    <div class="h-3 w-3 bg-white rounded-full animate-pulse"></div>
                                                @endif
                                            @else
                                                @if($stage->icon)
                                                    <x-dynamic-component :component="$stage->icon" class="h-5 w-5 {{ $isFuture ? 'text-gray-400 dark:text-gray-500 group-hover:text-' . $stage->color . '-500' : 'text-white' }}" />
                                                @else
                                                    <div class="h-2 w-2 {{ $isFuture ? 'bg-gray-400 dark:bg-gray-500' : 'bg-white' }} rounded-full"></div>
                                                @endif
                                            @endif
                                        </div>
                                        
                                        {{-- Hover Effect --}}
                                        @if($isFuture)
                                            <div class="absolute inset-0 rounded-full bg-{{ $stage->color }}-100 dark:bg-{{ $stage->color }}-900/20 scale-0 group-hover:scale-110 transition-transform duration-300 -z-10"></div>
                                        @endif
                                    </div>
                                    
                                    {{-- Stage Label --}}
                                    <div class="mt-3 text-center">
                                        <p class="text-xs font-medium leading-tight {{ $isCurrent ? 'text-' . $stage->color . '-700 dark:text-' . $stage->color . '-300' : ($isPast ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400') }}">
                                            {{ $stage->name }}
                                        </p>
                                        @if($isCurrent && $stage->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ Str::limit($stage->description, 50) }}
                                            </p>
                                        @endif
                                    </div>
                                    
                                    {{-- Stage Number/Order --}}
                                    <div class="absolute -top-2 -right-2 h-5 w-5 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $isCurrent ? 'bg-' . $stage->color . '-600 text-white' : ($isPast ? 'bg-green-600 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300') }}">
                                        {{ $loop->iteration }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Tabs Navigation - Responsive --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex flex-wrap sm:flex-nowrap gap-2 sm:gap-4">
                <button @click="activeTab = 'journey'" 
                        class="flex-1 sm:flex-initial py-2 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors"
                        :class="activeTab === 'journey' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    <span class="hidden sm:inline">Customer</span> Journey
                </button>
                <button @click="activeTab = 'touchpoints'" 
                        class="flex-1 sm:flex-initial py-2 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors"
                        :class="activeTab === 'touchpoints' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    Interaktionen
                </button>
                <button @click="activeTab = 'related'" 
                        class="flex-1 sm:flex-initial py-2 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors"
                        :class="activeTab === 'related' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    Verbindungen
                </button>
                <button @click="activeTab = 'notes'" 
                        class="flex-1 sm:flex-initial py-2 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors"
                        :class="activeTab === 'notes' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    Notizen
                </button>
            </nav>
        </div>
        
        {{-- Tab Content --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            {{-- Journey Tab --}}
            <div x-show="activeTab === 'journey'" class="p-4 space-y-4">
                @if(isset($journeyEvents) && count($journeyEvents) > 0)
                    <div class="space-y-3">
                        @foreach($journeyEvents as $event)
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <x-heroicon-o-arrow-path class="h-4 w-4 text-gray-500" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        Status geändert von <span class="font-medium">{{ $event->from_status ?? 'Neu' }}</span> 
                                        zu <span class="font-medium">{{ $event->to_status }}</span>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ Carbon::parse($event->created_at)->format('d.m.Y H:i') }}
                                        @if($event->triggered_by)
                                            • {{ $event->triggered_by }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Journey-Events vorhanden</p>
                @endif
                
                {{-- Nächste mögliche Status --}}
                @if($currentStage && $currentStage->next_stages)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nächste mögliche Schritte:</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($currentStage->next_stages as $nextStageCode)
                                @php
                                    $nextStage = $journeyStages->firstWhere('code', $nextStageCode);
                                @endphp
                                @if($nextStage)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-{{ $nextStage->color }}-100 text-{{ $nextStage->color }}-700 dark:bg-{{ $nextStage->color }}-900/20 dark:text-{{ $nextStage->color }}-300">
                                        {{ $nextStage->name }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Touchpoints Tab --}}
            <div x-show="activeTab === 'touchpoints'" class="p-4">
                @if(isset($touchpoints) && count($touchpoints) > 0)
                    <div class="space-y-3">
                        @foreach($touchpoints as $index => $touchpoint)
                            <div class="touchpoint-item {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}"
                                 x-show="showAllTouchpoints || {{ $index }} < 5"
                                 x-transition>
                                <div class="flex gap-3 pb-3">
                                    <div class="flex-shrink-0">
                                    @switch($touchpoint->type)
                                        @case('call')
                                            <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                                <x-heroicon-o-phone class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            @break
                                        @case('appointment')
                                            <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                                                <x-heroicon-o-calendar class="h-4 w-4 text-green-600 dark:text-green-400" />
                                            </div>
                                            @break
                                        @case('email')
                                            <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                                                <x-heroicon-o-envelope class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                            </div>
                                            @break
                                        @default
                                            <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                <x-heroicon-o-document-text class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                            </div>
                                    @endswitch
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ ucfirst($touchpoint->type) }}
                                        @if($touchpoint->channel)
                                            <span class="text-gray-500 dark:text-gray-400">über {{ $touchpoint->channel }}</span>
                                        @endif
                                    </p>
                                    @if($touchpoint->data)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">
                                            {{ json_decode($touchpoint->data)->summary ?? '' }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ Carbon::parse($touchpoint->occurred_at)->format('d.m.Y H:i') }}
                                    </p>
                                    </div>
                                    @if($touchpoint->touchpointable_type && $touchpoint->touchpointable_id)
                                        <div class="flex-shrink-0">
                                            <a href="#" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                                <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        
                        @if(count($touchpoints) > 5)
                            <button @click="showAllTouchpoints = true" 
                                    x-show="!showAllTouchpoints"
                                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                                Alle {{ count($touchpoints) }} Interaktionen anzeigen
                            </button>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Interaktionen erfasst</p>
                @endif
            </div>
            
            {{-- Related Customers Tab --}}
            <div x-show="activeTab === 'related'" class="p-4">
                @php
                    $interactions = $matchingService->getRelatedInteractions($customer);
                @endphp
                
                @if(count($interactions['related_customers']) > 0)
                    <div class="space-y-3">
                        @foreach($interactions['related_customers'] as $relatedCustomer)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $relatedCustomer->name }}
                                        @if($relatedCustomer->company_name)
                                            <span class="text-gray-500">({{ $relatedCustomer->company_name }})</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $relatedCustomer->phone }} • {{ $relatedCustomer->call_count }} Anrufe
                                    </p>
                                </div>
                                <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $relatedCustomer->id]) }}" 
                                   target="_blank"
                                   class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine verwandten Kunden gefunden</p>
                @endif
            </div>
            
            {{-- Notes Tab --}}
            <div x-show="activeTab === 'notes'" class="p-4">
                <div class="space-y-4">
                    @if($customer->internal_notes)
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($customer->internal_notes)) !!}
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">Keine internen Notizen vorhanden</p>
                    @endif
                    
                    @if($customer->tags && count(json_decode($customer->tags, true)) > 0)
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags:</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach(json_decode($customer->tags, true) as $tag)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
    @else
        {{-- Keine Kundenzuordnung - Zeige potenzielle Matches --}}
        <div class="mt-4">
            @if(isset($potentialMatches) && $potentialMatches->count() > 0)
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                    <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100 flex items-center gap-2 mb-3">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        Mögliche Kundenübereinstimmungen
                    </h3>
                    
                    <div class="space-y-2">
                        @foreach($potentialMatches->take(5) as $match)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-3">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $match->name }}
                                            </span>
                                            @if($match->company_name)
                                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                                    ({{ $match->company_name }})
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                {{ $match->match_confidence >= 90 ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 
                                                   ($match->match_confidence >= 70 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300' : 
                                                    'bg-gray-100 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300') }}">
                                                {{ $match->match_confidence }}% Match
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            <span class="inline-flex items-center gap-1">
                                                <x-heroicon-m-phone class="h-3 w-3" />
                                                {{ $match->phone }}
                                            </span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $match->call_count }} Anrufe</span>
                                            <span class="mx-1">•</span>
                                            <span>Status: {{ $journeyStages->firstWhere('code', $match->journey_status)?->name ?? 'Unbekannt' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button onclick="assignCustomerToCall({{ $record->id }}, {{ $match->id }})" 
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            Zuordnen
                                        </button>
                                        <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $match->id]) }}" 
                                           target="_blank"
                                           class="inline-flex items-center p-1.5 text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 rounded-md hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                                            <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700">
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            Ordnen Sie diesen Anruf einem bestehenden Kunden zu oder erstellen Sie einen neuen Kunden.
                        </p>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Kein Kunde zugeordnet. Verwenden Sie die Aktion "Kunde zuordnen" um die Customer Journey zu verfolgen.
                    </p>
                </div>
            @endif
        </div>
    @endif
</div>

<style>
/* Reset any potential Filament container issues */
.customer-journey-widget {
    width: 100%;
    max-width: 100%;
    overflow: visible;
}

/* Ensure proper spacing between sections */
.customer-journey-widget > div {
    margin-bottom: 1rem;
}

.customer-journey-widget > div:last-child {
    margin-bottom: 0;
}

/* Journey Progress Bar specific styling */
.journey-progress-container {
    display: flex;
    align-items: center;
    position: relative;
    padding-bottom: 40px;
}

.journey-stage-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 100px;
}

.journey-stage-circle {
    z-index: 2;
    position: relative;
}

.journey-stage-label {
    position: absolute;
    top: 100%;
    margin-top: 8px;
    text-align: center;
    width: 120px;
    left: 50%;
    transform: translateX(-50%);
}

.journey-connector {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    height: 2px;
    left: 50%;
    right: -50%;
    z-index: 1;
}

/* Mobile-first responsive styles */
@media (max-width: 640px) {
    .customer-journey-widget {
        margin-left: -1rem;
        margin-right: -1rem;
    }
    
    .customer-journey-widget > div {
        border-radius: 0;
        border-left: 0;
        border-right: 0;
    }
}

/* Timeline touchpoint styling */
.touchpoint-item {
    position: relative;
    overflow: visible;
}

.touchpoint-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 44px;
    bottom: -12px;
    width: 1px;
    background-color: rgb(229 231 235);
}

.dark .touchpoint-item:not(:last-child)::after {
    background-color: rgb(55 65 81);
}

/* Fix for potential Filament grid conflicts */
.fi-in-entry-wrp .customer-journey-widget {
    grid-column: 1 / -1;
}

/* Progress bar color classes - Tailwind dynamic class workaround */
.bg-blue-500 { background-color: rgb(59 130 246); }
.bg-blue-600 { background-color: rgb(37 99 235); }
.bg-green-500 { background-color: rgb(34 197 94); }
.bg-green-600 { background-color: rgb(22 163 74); }
.bg-amber-500 { background-color: rgb(245 158 11); }
.bg-amber-600 { background-color: rgb(217 119 6); }
.bg-red-500 { background-color: rgb(239 68 68); }
.bg-red-600 { background-color: rgb(220 38 38); }
.bg-purple-500 { background-color: rgb(168 85 247); }
.bg-purple-600 { background-color: rgb(147 51 234); }
.bg-emerald-500 { background-color: rgb(16 185 129); }
.bg-emerald-600 { background-color: rgb(5 150 105); }
.bg-gray-500 { background-color: rgb(107 114 128); }
.bg-gray-600 { background-color: rgb(75 85 99); }

/* Background hover colors */
.bg-blue-100 { background-color: rgb(219 234 254); }
.bg-green-100 { background-color: rgb(220 252 231); }
.bg-amber-100 { background-color: rgb(254 243 199); }
.bg-red-100 { background-color: rgb(254 226 226); }
.bg-purple-100 { background-color: rgb(243 232 255); }
.bg-emerald-100 { background-color: rgb(209 250 229); }

/* Ring colors */
.ring-blue-200 { --tw-ring-color: rgb(191 219 254); }
.ring-green-200 { --tw-ring-color: rgb(187 247 208); }
.ring-amber-200 { --tw-ring-color: rgb(254 215 170); }
.ring-red-200 { --tw-ring-color: rgb(254 202 202); }
.ring-purple-200 { --tw-ring-color: rgb(233 213 255); }
.ring-emerald-200 { --tw-ring-color: rgb(167 243 208); }

/* Dark mode ring colors */
.dark .ring-blue-800 { --tw-ring-color: rgb(30 64 175); }
.dark .ring-green-800 { --tw-ring-color: rgb(22 101 52); }
.dark .ring-amber-800 { --tw-ring-color: rgb(146 64 14); }
.dark .ring-red-800 { --tw-ring-color: rgb(153 27 27); }
.dark .ring-purple-800 { --tw-ring-color: rgb(91 33 182); }
.dark .ring-emerald-800 { --tw-ring-color: rgb(6 78 59); }

/* Text colors */
.text-blue-600 { color: rgb(37 99 235); }
.text-blue-700 { color: rgb(29 78 216); }
.text-green-600 { color: rgb(22 163 74); }
.text-green-700 { color: rgb(21 128 61); }
.text-amber-600 { color: rgb(217 119 6); }
.text-amber-700 { color: rgb(180 83 9); }
.text-red-600 { color: rgb(220 38 38); }
.text-red-700 { color: rgb(185 28 28); }
.text-purple-600 { color: rgb(147 51 234); }
.text-purple-700 { color: rgb(126 34 206); }
.text-emerald-600 { color: rgb(5 150 105); }
.text-emerald-700 { color: rgb(4 120 87); }

.dark .text-blue-300 { color: rgb(147 197 253); }
.dark .text-blue-400 { color: rgb(96 165 250); }
.dark .text-green-300 { color: rgb(134 239 172); }
.dark .text-green-400 { color: rgb(74 222 128); }
.dark .text-amber-300 { color: rgb(252 211 77); }
.dark .text-amber-400 { color: rgb(251 191 36); }
.dark .text-red-300 { color: rgb(252 165 165); }
.dark .text-red-400 { color: rgb(248 113 113); }
.dark .text-purple-300 { color: rgb(216 180 254); }
.dark .text-purple-400 { color: rgb(196 181 253); }
.dark .text-emerald-300 { color: rgb(110 231 183); }
.dark .text-emerald-400 { color: rgb(52 211 153); }

/* Hover text colors */
.hover\:text-blue-500:hover { color: rgb(59 130 246); }
.hover\:text-green-500:hover { color: rgb(34 197 94); }
.hover\:text-amber-500:hover { color: rgb(245 158 11); }
.hover\:text-red-500:hover { color: rgb(239 68 68); }
.hover\:text-purple-500:hover { color: rgb(168 85 247); }
.hover\:text-emerald-500:hover { color: rgb(16 185 129); }

/* Border colors */
.hover\:border-blue-400:hover { border-color: rgb(96 165 250); }
.hover\:border-green-400:hover { border-color: rgb(74 222 128); }
.hover\:border-amber-400:hover { border-color: rgb(251 191 36); }
.hover\:border-red-400:hover { border-color: rgb(248 113 113); }
.hover\:border-purple-400:hover { border-color: rgb(196 181 253); }
.hover\:border-emerald-400:hover { border-color: rgb(52 211 153); }
</style>

<script>
function assignCustomerToCall(callId, customerId) {
    // Zeige Loading-Status
    const button = event.target;
    const originalText = button.innerText;
    button.disabled = true;
    button.innerText = 'Zuordnen...';
    
    // AJAX Request
    fetch(`/admin/calls/${callId}/assign-customer`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            customer_id: customerId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Erfolgreich zugeordnet - Seite neu laden
            window.location.reload();
        } else {
            // Fehler anzeigen
            alert('Fehler beim Zuordnen: ' + (data.message || 'Unbekannter Fehler'));
            button.disabled = false;
            button.innerText = originalText;
        }
    })
    .catch(error => {
        console.error('Fehler:', error);
        alert('Fehler beim Zuordnen des Kunden');
        button.disabled = false;
        button.innerText = originalText;
    });
}
</script>