<div id="cookie-consent-banner" class="fixed bottom-0 left-0 right-0 bg-white shadow-2xl border-t border-gray-200 z-50 transform transition-transform duration-500"
     x-data="cookieConsent()"
     x-show="showBanner"
     x-transition:enter="transition ease-out duration-500"
     x-transition:enter-start="translate-y-full"
     x-transition:enter-end="translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="translate-y-0"
     x-transition:leave-end="translate-y-full"
     @cookie-consent-saved.window="hideBanner()">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="lg:flex lg:items-start lg:justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    {{ __('Diese Website verwendet Cookies') }}
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Wir verwenden Cookies und ähnliche Technologien, um Ihre Erfahrung zu verbessern, Inhalte zu personalisieren und die Leistung unserer Website zu analysieren.') }}
                </p>
                
                <!-- Quick Actions -->
                <div class="flex flex-wrap gap-3 mb-4 lg:hidden">
                    <button @click="acceptAll()" 
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ __('Alle akzeptieren') }}
                    </button>
                    <button @click="rejectAll()" 
                            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        {{ __('Nur notwendige') }}
                    </button>
                    <button @click="showDetails = !showDetails" 
                            class="px-4 py-2 text-blue-600 text-sm font-medium hover:text-blue-700">
                        {{ __('Einstellungen') }}
                    </button>
                </div>

                <!-- Detailed Settings -->
                <div x-show="showDetails" x-collapse class="mt-4 space-y-4">
                    @foreach(['necessary', 'functional', 'analytics', 'marketing'] as $category)
                        @php
                            $categoryData = $cookieCategories[$category] ?? [];
                        @endphp
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex-1">
                                    <label class="flex items-center justify-between cursor-pointer">
                                        <div class="flex-1 mr-3">
                                            <span class="font-medium text-gray-900">
                                                {{ $categoryData['name'] ?? ucfirst($category) }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $categoryData['description'] ?? '' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center">
                                            @if($category === 'necessary')
                                                <span class="text-xs text-gray-500 mr-2">{{ __('Immer aktiv') }}</span>
                                                <input type="checkbox" checked disabled
                                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded cursor-not-allowed opacity-50">
                                            @else
                                                <input type="checkbox" 
                                                       x-model="preferences.{{ $category }}_cookies"
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Cookie Details -->
                            <details class="mt-2">
                                <summary class="text-xs text-blue-600 cursor-pointer hover:text-blue-700">
                                    {{ __('Details anzeigen') }}
                                </summary>
                                <div class="mt-2 text-xs text-gray-600">
                                    @if(isset($categoryData['cookies']) && is_array($categoryData['cookies']))
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach($categoryData['cookies'] as $cookieName => $cookieDesc)
                                                <li><strong>{{ $cookieName }}:</strong> {{ $cookieDesc }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @endforeach

                    <div class="flex gap-3 pt-2">
                        <button @click="savePreferences()" 
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('Auswahl speichern') }}
                        </button>
                        <a href="{{ route('portal.cookie-policy') }}" target="_blank"
                           class="px-4 py-2 text-blue-600 text-sm font-medium hover:text-blue-700">
                            {{ __('Cookie-Richtlinie') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Desktop Quick Actions -->
            <div class="hidden lg:flex lg:items-center lg:space-x-3 lg:ml-8">
                <button @click="rejectAll()" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    {{ __('Nur notwendige') }}
                </button>
                <button @click="showDetails = !showDetails" 
                        class="px-4 py-2 text-blue-600 text-sm font-medium hover:text-blue-700">
                    {{ __('Einstellungen') }}
                </button>
                <button @click="acceptAll()" 
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ __('Alle akzeptieren') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        showBanner: false,
        showDetails: false,
        preferences: {
            functional_cookies: false,
            analytics_cookies: false,
            marketing_cookies: false
        },
        
        init() {
            // Check if consent already given
            fetch('/api/cookie-consent/status')
                .then(response => response.json())
                .then(data => {
                    if (!data.has_consent) {
                        this.showBanner = true;
                    }
                });
        },
        
        acceptAll() {
            this.preferences = {
                functional_cookies: true,
                analytics_cookies: true,
                marketing_cookies: true
            };
            this.save('/api/cookie-consent/accept-all');
        },
        
        rejectAll() {
            this.preferences = {
                functional_cookies: false,
                analytics_cookies: false,
                marketing_cookies: false
            };
            this.save('/api/cookie-consent/reject-all');
        },
        
        savePreferences() {
            this.save('/api/cookie-consent/save', this.preferences);
        },
        
        save(endpoint, data = {}) {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.hideBanner();
                    // Dispatch event for other components
                    window.dispatchEvent(new CustomEvent('cookie-consent-saved', { 
                        detail: this.preferences 
                    }));
                    // Reload to apply cookie settings
                    setTimeout(() => window.location.reload(), 500);
                }
            });
        },
        
        hideBanner() {
            this.showBanner = false;
        }
    }
}
</script>