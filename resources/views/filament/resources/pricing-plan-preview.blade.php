<div class="p-4 space-y-6">
    @php
        $plan = $record;
    @endphp

    {{-- Plan Header --}}
    <div class="text-center pb-4 border-b">
        <h2 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h2>
        @if($plan->description)
            <p class="mt-2 text-gray-600">{{ $plan->description }}</p>
        @endif
    </div>

    {{-- Pricing --}}
    <div class="text-center py-4">
        <div class="flex items-baseline justify-center">
            <span class="text-5xl font-extrabold text-gray-900">
                {{ number_format($plan->price, 0, ',', '.') }}
            </span>
            <span class="text-2xl font-medium text-gray-500 ml-1">€</span>
            @if($plan->billing_period)
                <span class="text-gray-500 ml-2">/ {{ $plan->billing_period }}</span>
            @endif
        </div>

        @if($plan->trial_days > 0)
            <p class="mt-2 text-sm text-green-600">
                {{ $plan->trial_days }} Tage kostenlos testen
            </p>
        @endif
    </div>

    {{-- Features --}}
    @if($plan->features && is_array($plan->features))
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Enthaltene Leistungen</h3>
            <ul class="space-y-2">
                @foreach($plan->features as $feature => $value)
                    <li class="flex items-start">
                        @if(is_bool($value) || $value === 'true' || $value === true)
                            <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                            <span class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $feature)) }}</span>
                        @elseif($value === 'false' || $value === false)
                            <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 mt-0.5 mr-2 flex-shrink-0" />
                            <span class="text-gray-400 line-through">{{ ucfirst(str_replace('_', ' ', $feature)) }}</span>
                        @else
                            <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                            <span class="text-gray-700">
                                {{ ucfirst(str_replace('_', ' ', $feature)) }}:
                                <span class="font-semibold">{{ $value }}</span>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Limits --}}
    @if($plan->limits && is_array($plan->limits))
        <div class="space-y-3 pt-4 border-t">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Limits & Kontingente</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach($plan->limits as $limit => $value)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $limit)) }}</div>
                        <div class="text-lg font-semibold text-gray-900">
                            @if(is_numeric($value))
                                {{ number_format($value, 0, ',', '.') }}
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Metadata --}}
    <div class="pt-4 border-t space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-gray-500">Status:</span>
            <span class="font-medium">
                @if($plan->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Aktiv
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Inaktiv
                    </span>
                @endif
            </span>
        </div>

        @if($plan->is_popular)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Beliebt:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <x-heroicon-s-star class="w-3 h-3 mr-1" />
                    Empfohlen
                </span>
            </div>
        @endif

        @if($plan->updated_at)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Zuletzt aktualisiert:</span>
                <span class="font-medium">{{ $plan->updated_at->format('d.m.Y H:i') }}</span>
            </div>
        @endif
    </div>
</div>