@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Abrechnung</h1>
            <p class="mt-1 text-sm text-gray-600">
                Verwalten Sie Ihr Guthaben und sehen Sie Ihre Nutzung ein
            </p>
        </div>

        <!-- Balance Overview -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Normal Balance -->
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Guthaben</h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ number_format($prepaidBalance->balance, 2, ',', '.') }} €
                    </p>
                    @if($reservedBalance > 0)
                        <p class="text-sm text-gray-500 mt-1">
                            ({{ number_format($reservedBalance, 2, ',', '.') }} € reserviert)
                        </p>
                    @endif
                </div>
                
                <!-- Bonus Balance -->
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Bonus-Guthaben</h3>
                    <p class="text-3xl font-bold text-green-600">
                        {{ number_format($bonusBalance, 2, ',', '.') }} €
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Nicht auszahlbar</p>
                </div>
                
                <!-- Total Balance -->
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Gesamt verfügbar</h3>
                    <p class="text-3xl font-bold {{ $totalBalance > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($totalBalance - $reservedBalance, 2, ',', '.') }} €
                    </p>
                </div>
                
                <!-- Available Minutes -->
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Verfügbare Minuten</h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ number_format($availableMinutes, 0, ',', '.') }} Min
                    </p>
                </div>
            </div>
            
            <!-- Auto-Topup Status -->
            @if($prepaidBalance->auto_topup_enabled)
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9.5H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium text-blue-900">
                            Auto-Aufladung aktiv: {{ number_format($prepaidBalance->auto_topup_amount, 2, ',', '.') }} € bei {{ number_format($prepaidBalance->auto_topup_threshold, 2, ',', '.') }} € Restguthaben
                        </span>
                    </div>
                    <a href="{{ route('business.billing.auto-topup') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        Einstellungen
                    </a>
                </div>
            </div>
            @endif
            
            <!-- Topup Section -->
            <div class="mt-6">
                @if($totalBalance - $reservedBalance < 50)
                    <div class="mb-4 p-4 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-800">
                            <strong>Niedriger Kontostand!</strong> Laden Sie Ihr Guthaben auf, um unterbrechungsfreien Service zu gewährleisten.
                        </p>
                    </div>
                @endif
                
                <!-- Bonus Rules Display -->
                @if(count($bonusRules) > 0)
                <div class="mb-4 p-4 bg-green-50 rounded-md">
                    <p class="text-sm font-medium text-green-800 mb-2">Aktuelle Bonus-Angebote:</p>
                    <ul class="text-sm text-green-700 space-y-1">
                        @foreach($bonusRules as $rule)
                        <li>• {{ $rule['description'] }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                <div class="text-center">
                    <a href="{{ route('business.billing.topup') }}" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Guthaben aufladen
                    </a>
                    @if(!$prepaidBalance->auto_topup_enabled)
                    <a href="{{ route('business.billing.auto-topup') }}" 
                       class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Auto-Aufladung einrichten
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Spending Limits Overview -->
        @if($spendingSummary['daily']['limit'] || $spendingSummary['weekly']['limit'] || $spendingSummary['monthly']['limit'])
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Ausgabenlimits
                </h3>
                <div class="space-y-4">
                    @if($spendingSummary['daily']['limit'])
                    <div>
                        <div class="flex justify-between text-sm font-medium text-gray-900 mb-1">
                            <span>Tägliches Limit</span>
                            <span>{{ number_format($spendingSummary['daily']['spent'], 2, ',', '.') }} / {{ number_format($spendingSummary['daily']['limit'], 2, ',', '.') }} €</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $spendingSummary['daily']['percentage']) }}%"></div>
                        </div>
                    </div>
                    @endif
                    
                    @if($spendingSummary['weekly']['limit'])
                    <div>
                        <div class="flex justify-between text-sm font-medium text-gray-900 mb-1">
                            <span>Wöchentliches Limit</span>
                            <span>{{ number_format($spendingSummary['weekly']['spent'], 2, ',', '.') }} / {{ number_format($spendingSummary['weekly']['limit'], 2, ',', '.') }} €</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $spendingSummary['weekly']['percentage']) }}%"></div>
                        </div>
                    </div>
                    @endif
                    
                    @if($spendingSummary['monthly']['limit'])
                    <div>
                        <div class="flex justify-between text-sm font-medium text-gray-900 mb-1">
                            <span>Monatliches Limit</span>
                            <span>{{ number_format($spendingSummary['monthly']['spent'], 2, ',', '.') }} / {{ number_format($spendingSummary['monthly']['limit'], 2, ',', '.') }} €</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $spendingSummary['monthly']['percentage']) }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Usage Statistics -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Nutzung diesen Monat
                </h3>
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Anrufe
                        </dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ $monthlyStats['total_calls'] ?? 0 }}
                        </dd>
                    </div>
                    <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Minuten
                        </dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ number_format($monthlyStats['total_minutes'] ?? 0, 0, ',', '.') }}
                        </dd>
                    </div>
                    <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Kosten
                        </dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ number_format($monthlyStats['total_charged'] ?? 0, 2, ',', '.') }} €
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Letzte Transaktionen
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Ihre letzten Transaktionen und Aufladungen
                </p>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <li class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($transaction->type === 'topup')
                                        <svg class="h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                        </svg>
                                    @elseif($transaction->type === 'bonus')
                                        <svg class="h-8 w-8 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @elseif($transaction->type === 'withdrawal')
                                        <svg class="h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="h-8 w-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $transaction->description }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $transaction->created_at->format('d.m.Y H:i') }}
                                        @if($transaction->affects_bonus)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Bonus
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 2, ',', '.') }} €
                                </div>
                                @if($transaction->bonus_amount != 0)
                                    <div class="text-xs text-yellow-600">
                                        {{ $transaction->bonus_amount > 0 ? '+' : '' }}{{ number_format($transaction->bonus_amount, 2, ',', '.') }} € Bonus
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-4 sm:px-6">
                        <p class="text-sm text-gray-500 text-center">Keine Transaktionen vorhanden</p>
                    </li>
                @endforelse
            </ul>
            @if($transactions->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        <!-- Quick Links -->
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <a href="{{ route('business.billing.transactions') }}" 
               class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    <p class="text-sm font-medium text-gray-900">
                        Alle Transaktionen
                    </p>
                    <p class="text-sm text-gray-500">
                        Detaillierte Transaktionshistorie ansehen
                    </p>
                </div>
            </a>

            <a href="{{ route('business.billing.usage') }}" 
               class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    <p class="text-sm font-medium text-gray-900">
                        Nutzungsstatistiken
                    </p>
                    <p class="text-sm text-gray-500">
                        Detaillierte Auswertungen und Berichte
                    </p>
                </div>
            </a>
            
            <a href="{{ route('business.billing.auto-topup') }}" 
               class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    <p class="text-sm font-medium text-gray-900">
                        Auto-Aufladung
                    </p>
                    <p class="text-sm text-gray-500">
                        Automatische Aufladung konfigurieren
                    </p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection