<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Guthaben aufladen - {{ $company->name }}</title>
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
    <link href="/css/app.css?v={{ time() }}" rel="stylesheet">
    <!-- Fallback to CDN for now, but with production warning suppressed -->
    <script>
        // Suppress Tailwind CDN warning
        window.console = window.console || {};
        const originalWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && args[0].includes('cdn.tailwindcss.com')) return;
            originalWarn.apply(console, args);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Mobile-optimierte Schriftgrößen */
        :root {
            --text-base: 16px;
            --text-lg: 18px;
            --text-xl: 20px;
            --button-text: 18px;
        }
        
        body {
            font-size: var(--text-base);
        }
        
        /* Mobile optimization for call info */
        @media (max-width: 380px) {
            .call-info-wrapper {
                flex-direction: column;
                align-items: flex-end;
                gap: 0.25rem;
            }
        }
        
        /* Touch-optimierte Buttons */
        .touch-button {
            min-height: 56px;
            font-size: 18px;
            touch-action: manipulation;
        }
        
        /* Smooth transitions */
        .amount-card {
            transition: all 0.2s ease;
        }
        
        .amount-card:active {
            transform: scale(0.98);
        }
        
        /* Custom number input styling */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        /* Bonus animation */
        @keyframes bonusPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .bonus-highlight {
            animation: bonusPulse 0.5s ease-in-out;
        }
        
        /* Info modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Compact guthaben widget */
        .guthaben-compact {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        
        /* Header transitions */
        #mainHeader {
            transition: padding 0.3s ease;
        }
        
        .header-scrolled {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
        
        .header-content * {
            transition: all 0.3s ease;
        }
        
        .header-scrolled .company-name {
            font-size: 0.875rem !important;
        }
        
        .header-scrolled .company-subtitle {
            display: none;
        }
        
        .header-scrolled .balance-widget {
            padding: 0.25rem 0.75rem !important;
        }
        
        .header-scrolled .balance-label {
            display: none;
        }
        
        .header-scrolled .balance-amount {
            font-size: 0.875rem !important;
        }
        
        /* Amount card improvements */
        .amount-option {
            display: block;
            padding: 0.75rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: visible;
        }
        
        .amount-option:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .amount-option.selected {
            border-color: #3b82f6;
            background: #f0f9ff;
        }
        
        /* Special styles for labeled cards */
        .amount-option.ring-2.ring-blue-400:hover {
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
        }
        
        .amount-option.ring-2.ring-purple-400:hover {
            box-shadow: 0 6px 20px rgba(147, 51, 234, 0.25);
        }
        
        .price-highlight {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.5rem;
            background: #fef3c7;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #92400e;
        }
        
        .bonus-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #10b981;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Enhanced Header mit Guthaben -->
    <header id="mainHeader" class="sticky top-0 z-40 bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg transition-all duration-300">
        <div class="max-w-lg mx-auto px-4 py-3 header-content">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-white company-name transition-all duration-300">{{ $company->name }}</h1>
                    <p class="text-xs text-blue-100 opacity-90 company-subtitle transition-all duration-300">Guthaben aufladen</p>
                </div>
                @if($currentBalance !== null)
                    <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg px-3 py-1.5 text-right balance-widget transition-all duration-300">
                        <div class="text-xs text-blue-100 font-medium balance-label transition-all duration-300">Aktuelles Guthaben</div>
                        <div class="font-bold text-base text-white balance-amount transition-all duration-300">
                            {{ number_format($currentBalance, 2, ',', '.') }}€
                        </div>
                        @if($currentBalance < 50)
                            <div class="mt-1 balance-progress">
                                <div class="w-24 h-1.5 bg-white bg-opacity-30 rounded-full overflow-hidden">
                                    <div class="h-full {{ $currentBalance < 20 ? 'bg-red-400' : 'bg-yellow-400' }} rounded-full transition-all duration-300" 
                                         style="width: {{ min(100, ($currentBalance / 50) * 100) }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            
        </div>
    </header>

    <div class="max-w-lg mx-auto px-4 py-6 pb-24">
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form id="topupForm" action="{{ route('public.topup.process', $company->id) }}" method="POST">
            @csrf
            
            <!-- Betrag wählen (Hauptbereich) -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-3">
                <h2 class="text-sm font-semibold mb-1 text-gray-900">Betrag wählen</h2>
                <p class="text-xs text-gray-500 mb-3">Je mehr Sie aufladen, desto günstiger wird der Minutenpreis</p>
                
                <!-- Quick Amount Selection mit integriertem Bonus -->
                <div class="mb-4">
                    @php
                        // Calculate effective price per minute for each amount
                        $basePrice = 0.42; // Base price per minute
                    @endphp
                    
                    <!-- Standard Beträge -->
                    <div class="flex flex-col space-y-2 mb-3">
                        @foreach(array_slice($suggestedAmounts, 0, 4) as $suggestion)
                            @php
                                $effectivePrice = $suggestion['amount'] / $suggestion['total'] * $basePrice;
                                $savings = (1 - ($effectivePrice / $basePrice)) * 100;
                            @endphp
                            <div onclick="selectAmount({{ $suggestion['amount'] }}, {{ $suggestion['bonus'] }})"
                                 class="amount-option group relative {{ $suggestion['amount'] == 500 ? 'ring-2 ring-blue-400' : '' }}">
                                
                                @if($suggestion['amount'] == 500)
                                    <!-- Label-Leiste für "Beliebt" -->
                                    <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-3 py-1.5 rounded-t-md">
                                        <div class="flex items-center justify-center gap-1.5 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span>BELIEBTESTE WAHL</span>
                                        </div>
                                    </div>
                                    <div class="pt-10">
                                @else
                                    <div>
                                @endif
                                
                                <!-- Hauptzeile -->
                                <div class="space-y-2 mb-2">
                                    <!-- Einzahlung und Guthaben -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs text-gray-500 mb-0.5">Sie zahlen:</div>
                                            <div class="text-xl font-bold text-gray-900">{{ number_format($suggestion['amount'], 0) }}€</div>
                                        </div>
                                        
                                        @if($suggestion['bonus'] > 0)
                                            <div class="flex items-center gap-2">
                                                <div class="text-center">
                                                    <div class="text-xs text-green-600 font-medium">+{{ number_format($suggestion['bonus'], 0) }}€</div>
                                                    <div class="text-xs text-green-600">Bonus</div>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                </svg>
                                            </div>
                                        @endif
                                        
                                        <div class="text-right">
                                            <div class="text-xs text-gray-500 mb-0.5">Ihr Guthaben:</div>
                                            <div class="text-xl font-bold {{ $suggestion['bonus'] > 0 ? 'text-green-600' : 'text-gray-900' }}">{{ number_format($suggestion['total'], 0) }}€</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Info-Zeile -->
                                <div class="flex items-center justify-between text-sm">
                                    <div class="price-highlight">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                                        </svg>
                                        <span>{{ number_format($effectivePrice, 2, ',', '.') }}€/Min</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        @php
                                            $totalMinutes = $suggestion['total'] / $basePrice;
                                            $totalCalls = floor($totalMinutes / 2); // 2 Minuten pro Gespräch
                                        @endphp
                                        <span>≈ {{ number_format($totalMinutes, 0) }} Min</span>
                                        <span class="text-gray-400">|</span>
                                        <span class="flex items-center gap-1" title="{{ number_format($totalCalls, 0) }} Gespräche à 2 Minuten">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                            </svg>
                                            {{ number_format($totalCalls, 0) }}
                                        </span>
                                    </div>
                                </div>
                                
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Premium Beträge (versteckt, aufklappbar) -->
                    <div id="premiumAmounts" class="hidden">
                        <div class="text-xs font-medium text-gray-500 mb-2">Premium-Aufladungen für Vieltelefonierer</div>
                        <div class="flex flex-col space-y-2">
                            @foreach(array_slice($suggestedAmounts, 4) as $suggestion)
                                @php
                                    $effectivePrice = $suggestion['amount'] / $suggestion['total'] * $basePrice;
                                    $savings = (1 - ($effectivePrice / $basePrice)) * 100;
                                @endphp
                                <div onclick="selectAmount({{ $suggestion['amount'] }}, {{ $suggestion['bonus'] }})"
                                     class="amount-option group relative {{ $suggestion['amount'] >= 3000 ? 'ring-2 ring-purple-400' : 'border-blue-200 bg-gradient-to-r from-white to-blue-50' }}">
                                    
                                    @if($suggestion['amount'] >= 3000)
                                        <!-- Label-Leiste für "Enterprise" -->
                                        <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-purple-600 to-purple-700 text-white px-3 py-1.5 rounded-t-md">
                                            <div class="flex items-center justify-center gap-1.5 text-sm font-semibold">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm6 6H7v2h6v-2z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>ENTERPRISE PAKET</span>
                                            </div>
                                        </div>
                                        <div class="pt-8">
                                    @else
                                        <div>
                                    @endif
                                    
                                    <!-- Hauptzeile -->
                                    <div class="space-y-2 mb-2">
                                        <!-- Einzahlung und Guthaben -->
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs text-gray-500 mb-0.5">Sie zahlen:</div>
                                                <div class="text-xl font-bold text-gray-900">{{ number_format($suggestion['amount'], 0) }}€</div>
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                <div class="text-center">
                                                    <div class="text-xs text-green-600 font-medium">+{{ number_format($suggestion['bonus'], 0) }}€</div>
                                                    <div class="text-xs text-green-600">Bonus</div>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                </svg>
                                            </div>
                                            
                                            <div class="text-right">
                                                <div class="text-xs text-gray-500 mb-0.5">Ihr Guthaben:</div>
                                                <div class="text-xl font-bold text-green-600">{{ number_format($suggestion['total'], 0) }}€</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Info-Zeile -->
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="price-highlight {{ $suggestion['amount'] >= 3000 ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                                                <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                                            </svg>
                                            <span>{{ number_format($effectivePrice, 2, ',', '.') }}€/Min</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-3 text-xs text-gray-600 font-medium">
                                            @php
                                                $totalMinutes = $suggestion['total'] / $basePrice;
                                                $totalCalls = floor($totalMinutes / 2); // 2 Minuten pro Gespräch
                                            @endphp
                                            <span>≈ {{ number_format($totalMinutes, 0) }} Min</span>
                                            <span class="text-gray-400">|</span>
                                            <span class="flex items-center gap-1" title="{{ number_format($totalCalls, 0) }} Gespräche à 2 Minuten">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                                </svg>
                                                {{ number_format($totalCalls, 0) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Toggle Button -->
                    <button type="button" onclick="togglePremiumAmounts()" 
                            class="w-full text-sm text-blue-600 hover:text-blue-700 font-medium mt-3 flex items-center justify-center">
                        <span id="toggleText">Große Beträge anzeigen</span>
                        <svg id="toggleIcon" class="w-4 h-4 ml-1 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Custom Amount -->
                <div class="relative mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <label for="amount" class="text-sm font-medium text-gray-700">
                            Oder eigenen Betrag eingeben:
                        </label>
                        <span class="text-xs text-gray-500">10€ - 10.000€</span>
                    </div>
                    <div class="relative">
                        <input id="amount" name="amount" type="number" step="0.01" min="10" max="10000" required 
                               value="{{ $presetAmount ?? '' }}"
                               oninput="handleCustomAmountInput(this.value)"
                               onfocus="clearAmountSelection()"
                               class="w-full px-3 py-2.5 pr-10 text-base border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                               placeholder="Betrag eingeben">
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-base pointer-events-none">€</span>
                    </div>
                    <div id="customBonusHint" class="mt-2 text-xs text-gray-600 bg-gray-50 rounded-md p-2 transition-all" style="display: none;"></div>
                </div>
            </div>

            <!-- Live Calculator (nur das Wichtigste) -->
            <div id="calculator" class="bg-blue-50 rounded-xl p-4 mb-3 border border-blue-200">
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Sie zahlen:</span>
                        <span class="font-bold text-lg text-gray-900" id="payAmount">0€</span>
                    </div>
                    <div id="bonusRow" class="flex justify-between items-center" style="display: none;">
                        <span class="text-green-700">Bonus geschenkt:</span>
                        <span class="font-bold text-lg text-green-600" id="bonusAmount">+0€</span>
                    </div>
                    <div class="pt-3 border-t border-blue-200">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-900">Guthaben danach:</span>
                            <span class="font-bold text-xl text-blue-900" id="totalAmount">0€</span>
                        </div>
                        @if($avgCallCost > 0)
                            <div class="text-sm text-blue-700 mt-1" id="callsInfo"></div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Hidden inputs for email/name (will be collected by Stripe) -->
            <input type="hidden" name="email" value="kunde@example.com">
            <input type="hidden" name="name" value="Kunde">

            <!-- Prominent Submit Button -->
            <button type="submit" 
                    class="touch-button w-full bg-blue-600 text-white font-semibold rounded-xl shadow-lg hover:bg-blue-700 active:bg-blue-800 transition-colors flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span id="buttonText">Jetzt aufladen</span>
            </button>
        </form>

        <!-- Trust Elements (minimal) -->
        <div class="mt-3 flex items-center justify-center space-x-3 text-xs text-gray-500">
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Stripe
            </span>
            <span>•</span>
            <span>SSL verschlüsselt</span>
        </div>
        
        <!-- Zusätzliche Informationen (unten) -->
        <div class="mt-8 space-y-6">
            <!-- Verwendungszweck & Transparenz -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="text-base font-semibold text-gray-900 mb-3">💡 So funktioniert Ihr Guthaben</h3>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>Ihr Guthaben wird automatisch für eingehende und ausgehende Anrufe verwendet</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>Ihr aktuelles Guthaben wird bei der nächsten Aufladung mitgenommen</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>Durchschnittliche Anrufdauer: {{ $avgCallMinutes ?? 3 }} Minuten</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>Automatische Aufladung verfügbar (in Ihren Einstellungen aktivierbar)</span>
                    </div>
                </div>
            </div>
            
            <!-- Bonus-Details (außerhalb des Modals) -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-4">
                <h3 class="text-base font-semibold text-gray-900 mb-3">🎁 Bonus-Übersicht</h3>
                
                <!-- Dynamische Tabelle basierend auf tatsächlichen Bonus-Regeln -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-xs text-gray-600">
                                <th class="pb-2">Aufladung</th>
                                <th class="pb-2">Bonus</th>
                                <th class="pb-2">Guthaben</th>
                                <th class="pb-2">€/Min</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $basePrice = 0.42;
                                $bonusExamples = [
                                    ['amount' => 50, 'bonus_percentage' => 0],
                                    ['amount' => 250, 'bonus_percentage' => 10],
                                    ['amount' => 500, 'bonus_percentage' => 15],
                                    ['amount' => 1000, 'bonus_percentage' => 20],
                                    ['amount' => 2000, 'bonus_percentage' => 30],
                                    ['amount' => 3000, 'bonus_percentage' => 40],
                                    ['amount' => 5000, 'bonus_percentage' => 50],
                                ];
                                
                                // Add actual bonus rules from controller data
                                if (isset($bonusRules) && count($bonusRules) > 0) {
                                    // Reset examples to show actual rules
                                    $bonusExamples = [];
                                    
                                    // Add example without bonus
                                    $bonusExamples[] = ['amount' => 50, 'bonus_percentage' => 0];
                                    
                                    // Add examples for each bonus rule
                                    foreach ($bonusRules as $rule) {
                                        if ($rule['min_amount'] >= 100) { // Skip first-time only rules for display
                                            $bonusExamples[] = [
                                                'amount' => $rule['min_amount'],
                                                'bonus_percentage' => $rule['bonus_percentage']
                                            ];
                                            
                                            // Add an example in the middle of the range if max_amount is set
                                            if ($rule['max_amount'] && $rule['max_amount'] > $rule['min_amount']) {
                                                $midAmount = ($rule['min_amount'] + $rule['max_amount']) / 2;
                                                $bonusExamples[] = [
                                                    'amount' => round($midAmount, -1), // Round to nearest 10
                                                    'bonus_percentage' => $rule['bonus_percentage']
                                                ];
                                            }
                                        }
                                    }
                                    
                                    // Sort by amount
                                    usort($bonusExamples, function($a, $b) {
                                        return $a['amount'] - $b['amount'];
                                    });
                                }
                            @endphp
                            
                            @foreach($bonusExamples as $example)
                                @php
                                    $bonusAmount = $example['amount'] * ($example['bonus_percentage'] / 100);
                                    $total = $example['amount'] + $bonusAmount;
                                    $effectivePrice = $total > 0 ? ($example['amount'] / $total) * $basePrice : $basePrice;
                                    $isHighlight = $example['amount'] >= 500;
                                @endphp
                                <tr class="{{ $isHighlight ? 'bg-blue-50' : '' }}">
                                    <td class="py-2 {{ $isHighlight ? 'font-medium' : '' }}">{{ number_format($example['amount'], 0, ',', '.') }}€</td>
                                    <td class="py-2 {{ $example['bonus_percentage'] > 0 ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                        {{ $example['bonus_percentage'] }}%
                                    </td>
                                    <td class="py-2 {{ $isHighlight ? 'font-medium' : '' }}">
                                        {{ number_format($total, 0, ',', '.') }}€
                                    </td>
                                    <td class="py-2 {{ $isHighlight ? 'font-medium' : '' }}">
                                        {{ number_format($effectivePrice, 2, ',', '.') }}€
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 text-xs text-gray-600">
                    <p class="font-medium">Wichtige Hinweise:</p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li>• Bonus wird automatisch nach erfolgreicher Zahlung gutgeschrieben</li>
                        <li>• Guthaben wird vor Bonus verbraucht</li>
                        <li>• Bonus ist nicht auszahlbar und 12 Monate gültig</li>
                        <li>• Minutenpreise basieren auf 0,42€/Min Grundpreis</li>
                        @if(count($bonusRules) > count(array_filter($bonusRules, fn($r) => !$r['is_first_time_only'])))
                            <li>• Neukunden erhalten einen zusätzlichen Willkommensbonus</li>
                        @endif
                    </ul>
                </div>
            </div>
            
            <!-- Weitere Informationen -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="text-base font-semibold text-gray-900 mb-3">ℹ️ Weitere Informationen</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Zahlungsmethoden</h4>
                        <ul class="space-y-1 text-xs text-gray-600">
                            <li>• Kreditkarte (Visa, Mastercard, Amex)</li>
                            <li>• SEPA-Lastschrift</li>
                            <li>• Sofortüberweisung</li>
                            <li>• PayPal (demnächst)</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Support & Hilfe</h4>
                        <p class="text-xs text-gray-600">
                            Bei Fragen oder Problemen erreichen Sie uns per E-Mail:<br>
                            <a href="mailto:fabian@askproai.de" class="text-blue-600 hover:underline font-medium">fabian@askproai.de</a>
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">💰 Rückerstattung</h4>
                    <p class="text-xs text-gray-600">
                        Nicht verbrauchtes Guthaben kann jederzeit zurückerstattet werden. 
                        Bonusguthaben ist von der Rückerstattung ausgeschlossen. 
                        Die Bearbeitung dauert in der Regel 5-7 Werktage.
                    </p>
                </div>
            </div>
            
            <!-- Rechtliche Hinweise -->
            <div class="text-center py-6 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    Mit Ihrer Zahlung akzeptieren Sie unsere 
                    <a href="/agb" class="text-blue-600 hover:underline">AGB</a> und 
                    <a href="/datenschutz" class="text-blue-600 hover:underline">Datenschutzerklärung</a>.
                    <br class="hidden md:inline">
                    Sie haben ein 14-tägiges Widerrufsrecht gemäß §355 BGB.
                </p>
            </div>
        </div>
    </div>


    <script>
        // Store average call cost for calculations
        const avgCallCost = {{ $avgCallCost ?? 0 }};
        const currentBalance = {{ $currentBalance ?? 0 }};
        
        // Header scroll behavior with debounce
        let scrollTimer = null;
        let isScrolled = false;
        
        function handleScroll() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const header = document.getElementById('mainHeader');
            
            if (scrollTop > 50 && !isScrolled) {
                header.classList.add('header-scrolled');
                isScrolled = true;
            } else if (scrollTop <= 50 && isScrolled) {
                header.classList.remove('header-scrolled');
                isScrolled = false;
            }
        }
        
        window.addEventListener('scroll', function() {
            if (scrollTimer !== null) {
                clearTimeout(scrollTimer);
            }
            scrollTimer = setTimeout(handleScroll, 10);
        }, { passive: true });
        
        // Initial check
        handleScroll();
        
        // Toggle premium amounts
        function togglePremiumAmounts() {
            const premiumDiv = document.getElementById('premiumAmounts');
            const toggleText = document.getElementById('toggleText');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (premiumDiv.classList.contains('hidden')) {
                premiumDiv.classList.remove('hidden');
                toggleText.textContent = 'Große Beträge ausblenden';
                toggleIcon.classList.add('rotate-180');
            } else {
                premiumDiv.classList.add('hidden');
                toggleText.textContent = 'Große Beträge anzeigen';
                toggleIcon.classList.remove('rotate-180');
            }
        }
        
        // Amount selection
        function selectAmount(amount, bonus) {
            document.getElementById('amount').value = amount;
            updateCalculator(amount);
            highlightSelectedAmount(amount);
            
            // Style the input field as filled
            const inputField = document.getElementById('amount');
            inputField.classList.add('bg-gray-50');
            inputField.classList.remove('bg-white');
        }
        
        // Clear amount selection when focusing on custom input
        function clearAmountSelection() {
            document.querySelectorAll('.amount-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Reset input field style
            const inputField = document.getElementById('amount');
            inputField.classList.remove('bg-gray-50');
            inputField.classList.add('bg-white');
        }
        
        // Handle custom amount input
        function handleCustomAmountInput(value) {
            updateCalculator(value);
            clearAmountSelection();
        }
        
        // Highlight selected amount card
        function highlightSelectedAmount(amount) {
            document.querySelectorAll('.amount-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Find and highlight the selected option
            const selectedOption = Array.from(document.querySelectorAll('.amount-option')).find(option => 
                option.textContent.includes(amount + '€') && option.textContent.includes('Sie zahlen')
            );
            
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }
        }
        
        // Update calculator
        function updateCalculator(amount) {
            const amountNum = parseFloat(amount) || 0;
            
            // Calculate bonus based on actual bonus rules
            let bonusPercentage = 0;
            @if(isset($bonusRules) && count($bonusRules) > 0)
                // Use actual bonus rules from backend
                @foreach($bonusRules as $rule)
                    @if(!$rule['is_first_time_only'])
                        if (amountNum >= {{ $rule['min_amount'] }} @if($rule['max_amount']) && amountNum <= {{ $rule['max_amount'] }} @endif) {
                            bonusPercentage = {{ $rule['bonus_percentage'] }};
                        }
                    @endif
                @endforeach
            @else
                // Fallback to default rules matching PrepaidBillingService
                if (amountNum >= 5000) {
                    bonusPercentage = 50;
                } else if (amountNum >= 3000) {
                    bonusPercentage = 40;
                } else if (amountNum >= 2000) {
                    bonusPercentage = 30;
                } else if (amountNum >= 1000) {
                    bonusPercentage = 20;
                } else if (amountNum >= 500) {
                    bonusPercentage = 15;
                } else if (amountNum >= 250) {
                    bonusPercentage = 10;
                }
            @endif
            
            const bonusAmount = amountNum * (bonusPercentage / 100);
            const total = amountNum + bonusAmount;
            const newBalance = currentBalance + total;
            
            // Update displays
            document.getElementById('payAmount').textContent = amountNum.toFixed(2).replace('.', ',') + '€';
            
            const bonusRow = document.getElementById('bonusRow');
            if (bonusPercentage > 0) {
                bonusRow.style.display = 'flex';
                document.getElementById('bonusAmount').textContent = '+' + bonusAmount.toFixed(2).replace('.', ',') + '€';
            } else {
                bonusRow.style.display = 'none';
            }
            
            document.getElementById('totalAmount').textContent = total.toFixed(2).replace('.', ',') + '€';
            
            // Update calls info
            if (avgCallCost > 0 && total > 0) {
                const totalCalls = Math.floor(newBalance / avgCallCost);
                document.getElementById('callsInfo').textContent = `Das sind insgesamt ~${totalCalls} Anrufe`;
            }
            
            // Update button
            const buttonText = document.getElementById('buttonText');
            if (amountNum >= 10) {
                buttonText.textContent = 'Jetzt ' + amountNum.toFixed(0) + '€ aufladen';
            } else {
                buttonText.textContent = 'Jetzt aufladen';
            }
            
            // Update custom bonus hint
            const hint = document.getElementById('customBonusHint');
            const inputField = document.getElementById('amount');
            
            // Only show hint when typing in custom field
            if (document.activeElement === inputField && amountNum > 0) {
                if (amountNum >= 50 && amountNum < 250) {
                    hint.innerHTML = '💡 <strong>Noch ' + (250 - amountNum).toFixed(0) + '€ bis zum 10% Bonus!</strong>';
                    hint.style.display = 'block';
                } else if (amountNum >= 250 && amountNum < 500) {
                    hint.innerHTML = '✅ Sie erhalten <strong>10% Bonus</strong> • Noch ' + (500 - amountNum).toFixed(0) + '€ bis 15%';
                    hint.style.display = 'block';
                } else if (amountNum >= 500 && amountNum < 1000) {
                    hint.innerHTML = '✅ Sie erhalten <strong>15% Bonus</strong> • Noch ' + (1000 - amountNum).toFixed(0) + '€ bis 20%';
                    hint.style.display = 'block';
                } else if (amountNum >= 1000 && amountNum < 2000) {
                    hint.innerHTML = '✅ Sie erhalten <strong>20% Bonus</strong> • Noch ' + (2000 - amountNum).toFixed(0) + '€ bis 30%';
                    hint.style.display = 'block';
                } else if (amountNum >= 2000 && amountNum < 3000) {
                    hint.innerHTML = '✅ Sie erhalten <strong>30% Bonus</strong> • Noch ' + (3000 - amountNum).toFixed(0) + '€ bis 40%';
                    hint.style.display = 'block';
                } else if (amountNum >= 3000 && amountNum < 5000) {
                    hint.innerHTML = '✅ Sie erhalten <strong>40% Bonus</strong> • Noch ' + (5000 - amountNum).toFixed(0) + '€ bis 50%';
                    hint.style.display = 'block';
                } else if (amountNum >= 5000) {
                    hint.innerHTML = '🎉 <strong>Maximaler Bonus: 50%!</strong>';
                    hint.style.display = 'block';
                } else {
                    hint.style.display = 'none';
                }
            } else {
                hint.style.display = 'none';
            }
            
            // Highlight calculator based on bonus
            const calculator = document.getElementById('calculator');
            calculator.className = bonusPercentage >= 15 ? 
                'bg-green-50 rounded-xl p-4 mb-3 border border-green-200 transition-all duration-300' :
                bonusPercentage > 0 ?
                'bg-orange-50 rounded-xl p-4 mb-3 border border-orange-200 transition-all duration-300' :
                'bg-blue-50 rounded-xl p-4 mb-3 border border-blue-200 transition-all duration-300';
        }
        
        // Form validation
        document.getElementById('topupForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            if (amount < 10 || amount > 10000) {
                e.preventDefault();
                alert('Bitte geben Sie einen Betrag zwischen 10€ und 10.000€ ein.');
                return;
            }
            
            // Stripe will collect email and name
            // For now, use placeholder values that will be replaced in Stripe Checkout
            document.querySelector('input[name="email"]').value = 'checkout@stripe.com';
            document.querySelector('input[name="name"]').value = 'Stripe Checkout';
        });
        
        // Initialize with recommended amount
        @if($presetAmount)
            selectAmount({{ $presetAmount }}, {{ $presetBonus }});
        @else
            window.addEventListener('load', function() {
                selectAmount(500, 75);
            });
        @endif
    </script>
</body>
</html>