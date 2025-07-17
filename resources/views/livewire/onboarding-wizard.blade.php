<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header with Progress -->
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">AskProAI Setup Wizard</h1>
                    <p class="text-sm text-gray-600">In 5 Minuten zur AI-Telefonanlage</p>
                </div>
                
                <!-- Timer -->
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $timeRemaining < 60 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ floor($timeRemaining / 60) }}:{{ str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <div class="text-xs text-gray-500">Zeit verbleibend</div>
                    </div>
                    
                    @if($timeElapsed <= 300)
                        <div class="text-green-500">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="pb-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-500"
                                 style="width: {{ $progressPercentage }}%"></div>
                        </div>
                    </div>
                    <div class="ml-4 text-sm font-medium text-gray-900">
                        Schritt {{ $currentStep }} von 7
                    </div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex justify-between mt-2">
                    @foreach(['Willkommen', 'Firmendaten', 'API Setup', 'AI-Agent', 'Services', 'Öffnungszeiten', 'Test & Launch'] as $index => $step)
                        <div class="text-xs {{ $currentStep > $index + 1 ? 'text-green-600' : ($currentStep == $index + 1 ? 'text-blue-600 font-semibold' : 'text-gray-400') }}">
                            {{ $step }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(!$isCompleted)
            <!-- Step 1: Welcome -->
            @if($currentStep == 1)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center mb-8">
                        <div class="mx-auto w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Willkommen beim 5-Minuten Setup!</h2>
                        <p class="text-lg text-gray-600">Richten Sie Ihre AI-Telefonanlage schnell und einfach ein.</p>
                    </div>

                    @if($showVideo)
                        <div class="mb-8">
                            <div class="aspect-w-16 aspect-h-9 bg-gray-200 rounded-lg">
                                <!-- Video placeholder -->
                                <div class="flex items-center justify-center">
                                    <p class="text-gray-500">Demo Video (30 Sekunden)</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="text-center">
                                <div class="text-4xl mb-2">📞</div>
                                <h3 class="font-semibold">24/7 Erreichbar</h3>
                                <p class="text-sm text-gray-600 mt-1">Nie wieder verpasste Anrufe</p>
                            </div>
                            <div class="text-center">
                                <div class="text-4xl mb-2">🤖</div>
                                <h3 class="font-semibold">KI-Assistent</h3>
                                <p class="text-sm text-gray-600 mt-1">Professionell & freundlich</p>
                            </div>
                            <div class="text-center">
                                <div class="text-4xl mb-2">📅</div>
                                <h3 class="font-semibold">Auto-Termine</h3>
                                <p class="text-sm text-gray-600 mt-1">Direkt im Kalender</p>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg">Was Sie benötigen:</h3>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded text-blue-600" checked disabled>
                                <span class="ml-2 text-gray-700">5 Minuten Zeit</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded text-blue-600">
                                <span class="ml-2 text-gray-700">Firmendaten (Name, Adresse, etc.)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded text-blue-600">
                                <span class="ml-2 text-gray-700">API Keys (wir helfen dabei!)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button wire:click="$set('showVideo', true)" class="text-blue-600 hover:text-blue-700 font-medium">
                            Demo ansehen (30 Sek)
                        </button>
                        <button wire:click="nextStep" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Los geht's! →
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 2: Company Data -->
            @if($currentStep == 2)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Ihre Firmendaten</h2>
                    
                    <!-- Industry Selection -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Wählen Sie Ihre Branche</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($industryTemplates->take(6) as $template)
                                <button wire:click="selectTemplate('{{ $template->slug }}')"
                                        class="p-4 rounded-lg border-2 transition-all {{ $selectedTemplate == $template->slug ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400' }}">
                                    <div class="text-3xl mb-2">{{ $template->icon == 'heroicon-o-heart' ? '❤️' : ($template->icon == 'heroicon-o-scissors' ? '✂️' : '🏢') }}</div>
                                    <div class="font-medium">{{ $template->name }}</div>
                                </button>
                            @endforeach
                        </div>
                        @error('selectedTemplate') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Company Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Firmenname</label>
                            <input wire:model.live="companyData.name" type="text" 
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Mustermann GmbH">
                            @error('companyData.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                            <input wire:model.live="companyData.email" type="email" 
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="info@firma.de">
                            @error('companyData.email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefonnummer</label>
                            <input wire:model.live="companyData.phone" type="tel" 
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="+49 123 456789">
                            @error('companyData.phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stadt</label>
                            <input wire:model.live="companyData.city" type="text" 
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Berlin">
                            @error('companyData.city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                        <input wire:model.live="companyData.address" type="text" 
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="Musterstraße 123">
                        @error('companyData.address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button wire:click="previousStep" class="text-gray-600 hover:text-gray-800 font-medium">
                            ← Zurück
                        </button>
                        <button wire:click="nextStep" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Weiter →
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 3: API Setup -->
            @if($currentStep == 3)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">API Verbindungen</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800">
                            <strong>Tipp:</strong> Klicken Sie auf die QR-Codes oder Links, um direkt zu den API-Seiten zu gelangen.
                            Wir validieren Ihre Keys automatisch!
                        </p>
                    </div>

                    <!-- Retell.ai API -->
                    <div class="mb-8 p-6 border rounded-lg {{ $validationStatus['retell'] == 'valid' ? 'border-green-500 bg-green-50' : ($validationStatus['retell'] == 'invalid' ? 'border-red-500 bg-red-50' : 'border-gray-300') }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg mb-2">Retell.ai API Key</h3>
                                <p class="text-sm text-gray-600 mb-4">Für die KI-Telefonfunktion</p>
                                
                                <div class="relative">
                                    <input wire:model.live="apiKeys.retell" 
                                           wire:blur="validateApiKey('retell')"
                                           type="password" 
                                           class="w-full px-4 py-2 pr-12 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                           placeholder="key_e973c8962e09d6a34b3b1cf386">
                                    
                                    @if($validationStatus['retell'] == 'valid')
                                        <div class="absolute right-3 top-2.5 text-green-500">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @elseif($validationStatus['retell'] == 'invalid')
                                        <div class="absolute right-3 top-2.5 text-red-500">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                @error('apiKeys.retell') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                <a href="https://retellai.com/dashboard/api-keys" target="_blank" class="inline-flex items-center mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    API Key erstellen →
                                </a>
                            </div>
                            
                            <div class="ml-6 flex-shrink-0">
                                <div class="w-32 h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-500">QR Code</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cal.com API -->
                    <div class="mb-8 p-6 border rounded-lg {{ $validationStatus['calcom'] == 'valid' ? 'border-green-500 bg-green-50' : ($validationStatus['calcom'] == 'invalid' ? 'border-red-500 bg-red-50' : 'border-gray-300') }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg mb-2">Cal.com API Key</h3>
                                <p class="text-sm text-gray-600 mb-4">Für die Kalenderfunktion</p>
                                
                                <div class="relative">
                                    <input wire:model.live="apiKeys.calcom" 
                                           wire:blur="validateApiKey('calcom')"
                                           type="password" 
                                           class="w-full px-4 py-2 pr-12 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                           placeholder="cal_live_1234567890abcdef">
                                    
                                    @if($validationStatus['calcom'] == 'valid')
                                        <div class="absolute right-3 top-2.5 text-green-500">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @elseif($validationStatus['calcom'] == 'invalid')
                                        <div class="absolute right-3 top-2.5 text-red-500">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                @error('apiKeys.calcom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                <a href="https://app.cal.com/settings/developer/api-keys" target="_blank" class="inline-flex items-center mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    API Key erstellen →
                                </a>
                            </div>
                            
                            <div class="ml-6 flex-shrink-0">
                                <div class="w-32 h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-500">QR Code</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service Option -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="font-semibold text-lg mb-2">🎯 Setup-Service</h3>
                        <p class="text-sm text-gray-700 mb-3">
                            Keine Zeit für die technischen Details? Wir richten alles für Sie ein!
                        </p>
                        <button class="bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700 font-medium">
                            Setup für mich durchführen (+49€)
                        </button>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button wire:click="previousStep" class="text-gray-600 hover:text-gray-800 font-medium">
                            ← Zurück
                        </button>
                        <button wire:click="nextStep" 
                                {{ ($validationStatus['retell'] !== 'valid' || $validationStatus['calcom'] !== 'valid') ? 'disabled' : '' }}
                                class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                            Weiter →
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 4: AI Configuration -->
            @if($currentStep == 4)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">KI-Assistent konfigurieren</h2>
                    
                    @if($selectedIndustryTemplate)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-blue-800">
                                Basierend auf Ihrer Branche <strong>{{ $selectedIndustryTemplate->name }}</strong> haben wir 
                                optimale Einstellungen vorgenommen.
                            </p>
                        </div>
                    @endif

                    <!-- Voice Preview -->
                    <div class="mb-8">
                        <h3 class="font-semibold text-lg mb-4">Stimme & Persönlichkeit</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button wire:click="$set('aiConfig.tone', 'professional')"
                                    class="p-4 rounded-lg border-2 {{ ($aiConfig['tone'] ?? '') == 'professional' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                                <div class="text-2xl mb-2">👔</div>
                                <div class="font-medium">Professionell</div>
                                <div class="text-sm text-gray-600">Förmlich & kompetent</div>
                            </button>
                            
                            <button wire:click="$set('aiConfig.tone', 'friendly')"
                                    class="p-4 rounded-lg border-2 {{ ($aiConfig['tone'] ?? '') == 'friendly' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                                <div class="text-2xl mb-2">😊</div>
                                <div class="font-medium">Freundlich</div>
                                <div class="text-sm text-gray-600">Warmherzig & hilfsbereit</div>
                            </button>
                            
                            <button wire:click="$set('aiConfig.tone', 'energetic')"
                                    class="p-4 rounded-lg border-2 {{ ($aiConfig['tone'] ?? '') == 'energetic' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                                <div class="text-2xl mb-2">⚡</div>
                                <div class="font-medium">Energisch</div>
                                <div class="text-sm text-gray-600">Motivierend & aktiv</div>
                            </button>
                        </div>
                    </div>

                    <!-- Greeting Preview -->
                    <div class="mb-8">
                        <h3 class="font-semibold text-lg mb-4">Begrüßung</h3>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-gray-800">
                                        @php
                                            $greeting = $aiConfig['greeting'] ?? 'Guten Tag, Sie haben die Praxis erreicht. Wie kann ich Ihnen helfen?';
                                            $companyName = $companyData['name'] ?? 'Ihre Firma';
                                            $greeting = str_replace('{{company_name}}', $companyName, $greeting);
                                        @endphp
                                        {{ $greeting }}
                                    </p>
                                    
                                    <button class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Vorschau anhören
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Begrüßungstext anpassen</label>
                            <textarea wire:model="aiConfig.greeting" 
                                      rows="3"
                                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                      placeholder="Guten Tag, Sie haben {{company_name}} erreicht..."></textarea>
                        </div>
                    </div>

                    <!-- Common Questions -->
                    <div class="mb-8">
                        <h3 class="font-semibold text-lg mb-4">Häufige Fragen trainieren</h3>
                        <p class="text-sm text-gray-600 mb-4">Diese Fragen kann Ihr KI-Assistent besonders gut beantworten:</p>
                        <div class="space-y-2">
                            @foreach($selectedIndustryTemplate->common_questions ?? [] as $question)
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">{{ $question }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button wire:click="previousStep" class="text-gray-600 hover:text-gray-800 font-medium">
                            ← Zurück
                        </button>
                        <button wire:click="nextStep" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Weiter →
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 5: Services -->
            @if($currentStep == 5)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Ihre Dienstleistungen</h2>
                    
                    <p class="text-gray-600 mb-6">Wir haben basierend auf Ihrer Branche die häufigsten Services vorausgewählt.</p>

                    <div class="space-y-4">
                        @foreach($services as $index => $service)
                            <div class="flex items-center p-4 border rounded-lg">
                                <input type="checkbox" 
                                       wire:model="services.{{ $index }}.active"
                                       class="rounded text-blue-600 mr-4" 
                                       checked>
                                
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Service</label>
                                        <input type="text" 
                                               wire:model="services.{{ $index }}.name"
                                               class="mt-1 w-full px-3 py-1 border rounded focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Dauer (Min)</label>
                                        <input type="number" 
                                               wire:model="services.{{ $index }}.duration"
                                               class="mt-1 w-full px-3 py-1 border rounded focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Preis (€)</label>
                                        <input type="number" 
                                               wire:model="services.{{ $index }}.price"
                                               class="mt-1 w-full px-3 py-1 border rounded focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                
                                <button wire:click="removeService({{ $index }})" class="ml-4 text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button wire:click="addService" class="mt-4 text-blue-600 hover:text-blue-700 font-medium">
                        + Service hinzufügen
                    </button>

                    <div class="mt-8 flex justify-between">
                        <button wire:click="previousStep" class="text-gray-600 hover:text-gray-800 font-medium">
                            ← Zurück
                        </button>
                        <button wire:click="nextStep" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Weiter →
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 6: Working Hours -->
            @if($currentStep == 6)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Öffnungszeiten</h2>
                    
                    <div class="mb-6 flex space-x-4">
                        <button class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Normale Bürozeiten
                        </button>
                        <button class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Praxis-Zeiten
                        </button>
                        <button class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Salon-Zeiten
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach(['monday' => 'Montag', 'tuesday' => 'Dienstag', 'wednesday' => 'Mittwoch', 'thursday' => 'Donnerstag', 'friday' => 'Freitag', 'saturday' => 'Samstag', 'sunday' => 'Sonntag'] as $day => $dayName)
                            <div class="flex items-center">
                                <div class="w-32">
                                    <label class="font-medium">{{ $dayName }}</label>
                                </div>
                                
                                <input type="checkbox" 
                                       wire:model="workingHours.{{ $day }}.open"
                                       class="rounded text-blue-600 mr-4"
                                       {{ $workingHours[$day] ? 'checked' : '' }}>
                                
                                @if($workingHours[$day] ?? false)
                                    <div class="flex items-center space-x-2">
                                        <input type="time" 
                                               wire:model="workingHours.{{ $day }}.0"
                                               class="px-3 py-1 border rounded"
                                               value="{{ $workingHours[$day][0] ?? '09:00' }}">
                                        <span>-</span>
                                        <input type="time" 
                                               wire:model="workingHours.{{ $day }}.1"
                                               class="px-3 py-1 border rounded"
                                               value="{{ $workingHours[$day][1] ?? '18:00' }}">
                                        
                                        @if(count($workingHours[$day] ?? []) > 2)
                                            <span class="ml-4">Pause:</span>
                                            <input type="time" 
                                                   wire:model="workingHours.{{ $day }}.1"
                                                   class="px-3 py-1 border rounded">
                                            <span>-</span>
                                            <input type="time" 
                                                   wire:model="workingHours.{{ $day }}.2"
                                                   class="px-3 py-1 border rounded">
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-500">Geschlossen</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Tipp:</strong> Feiertage werden automatisch berücksichtigt.
                        </p>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button wire:click="previousStep" class="text-gray-600 hover:text-gray-800 font-medium">
                            ← Zurück
                        </button>
                        <button wire:click="nextStep" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Weiter →
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 7: Test & Launch -->
            @if($currentStep == 7)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Test & Live gehen</h2>
                    
                    <div class="text-center mb-8">
                        <div class="mx-auto w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">Fast geschafft!</h3>
                        <p class="text-gray-600 mt-2">Testen Sie jetzt Ihren KI-Assistenten mit einem Anruf.</p>
                    </div>

                    <!-- Test Call Section -->
                    <div class="bg-blue-50 rounded-lg p-6 mb-8">
                        <h4 class="font-semibold mb-4">Testanruf starten</h4>
                        <p class="text-sm text-gray-700 mb-4">
                            Wir rufen Sie jetzt auf Ihrer angegebenen Nummer an. Sprechen Sie mit Ihrem KI-Assistenten!
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">Ihre Nummer:</p>
                                <p class="text-lg">{{ $companyData['phone'] ?? '+49 123 456789' }}</p>
                            </div>
                            
                            <button wire:click="testCall" 
                                    wire:loading.attr="disabled"
                                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold disabled:opacity-50">
                                <span wire:loading.remove wire:target="testCall">📞 Jetzt anrufen</span>
                                <span wire:loading wire:target="testCall">Rufe an...</span>
                            </button>
                        </div>
                        
                        <!-- Live Transcript -->
                        <div x-data="{ transcript: '' }" 
                             x-on:test-call-complete.window="transcript = $event.detail.transcript"
                             x-show="transcript"
                             class="mt-6 p-4 bg-white rounded-lg">
                            <h5 class="font-medium mb-2">Live-Transkript:</h5>
                            <p class="text-sm text-gray-700" x-text="transcript"></p>
                        </div>
                    </div>

                    <!-- Setup Summary -->
                    <div class="border rounded-lg p-6 mb-8">
                        <h4 class="font-semibold mb-4">Ihre Konfiguration</h4>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="font-medium text-gray-500">Firma:</dt>
                                <dd>{{ $companyData['name'] ?? 'Nicht angegeben' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Branche:</dt>
                                <dd>{{ $selectedIndustryTemplate->name ?? 'Nicht ausgewählt' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Services:</dt>
                                <dd>{{ count(array_filter($services, fn($s) => $s['active'] ?? true)) }} aktiv</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Setup-Zeit:</dt>
                                <dd class="{{ $timeElapsed <= 300 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ floor($timeElapsed / 60) }}:{{ str_pad($timeElapsed % 60, 2, '0', STR_PAD_LEFT) }} Min
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Launch Button -->
                    <div class="text-center">
                        <button wire:click="completeOnboarding" 
                                class="bg-green-600 text-white px-12 py-4 rounded-lg hover:bg-green-700 font-semibold text-lg transform hover:scale-105 transition-transform">
                            🚀 Jetzt live gehen!
                        </button>
                        <p class="text-sm text-gray-600 mt-4">
                            Sie können alle Einstellungen später im Dashboard anpassen.
                        </p>
                    </div>
                </div>
            @endif
        @else
            <!-- Completion Screen -->
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mx-auto w-32 h-32 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Herzlichen Glückwunsch! 🎉</h2>
                <p class="text-lg text-gray-600 mb-8">
                    Ihre AI-Telefonanlage ist jetzt aktiv und empfängt Anrufe.
                </p>
                
                <div class="bg-green-50 rounded-lg p-6 mb-8">
                    <h3 class="font-semibold mb-4">Setup-Statistiken</h3>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <div class="text-3xl font-bold text-green-600">{{ floor($timeElapsed / 60) }}:{{ str_pad($timeElapsed % 60, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="text-sm text-gray-600">Setup-Zeit</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-green-600">{{ $timeElapsed <= 300 ? '✅' : '⏱️' }}</div>
                            <div class="text-sm text-gray-600">{{ $timeElapsed <= 300 ? 'Unter 5 Min!' : 'Abgeschlossen' }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h3 class="font-semibold">Ihre nächsten Schritte:</h3>
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Dashboard erkunden</span>
                    </div>
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Erste Anrufe empfangen</span>
                    </div>
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Team einladen</span>
                    </div>
                </div>
                
                <div class="mt-8">
                    <a href="/admin" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold inline-block">
                        Zum Dashboard →
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Scripts -->
    @push('scripts')
    <script>
        // Timer functionality
        let startTime = Date.now() - ({{ $timeElapsed }} * 1000);
        let timerInterval;
        
        window.addEventListener('start-timer', () => {
            timerInterval = setInterval(() => {
                let elapsed = Math.floor((Date.now() - startTime) / 1000);
                Livewire.dispatch('updateTimer', { seconds: elapsed });
            }, 1000);
        });
        
        // Confetti effect
        window.addEventListener('show-confetti', () => {
            // Add confetti library and trigger
            console.log('🎉 Confetti!');
        });
        
        // Redirect to dashboard
        window.addEventListener('redirect-to-dashboard', () => {
            setTimeout(() => {
                window.location.href = '/admin';
            }, 3000);
        });
    </script>
    @endpush
</div>