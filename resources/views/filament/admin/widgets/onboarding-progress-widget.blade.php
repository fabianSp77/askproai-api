<x-filament-widgets::widget>
    <x-filament::section>
        @if(!$hasStarted)
            <div class="text-center py-8">
                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-rocket-launch class="w-8 h-8 text-blue-600" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Willkommen bei AskProAI!
                </h3>
                <p class="text-gray-600 mb-6">
                    Richten Sie Ihre AI-Telefonanlage in nur 5 Minuten ein.
                </p>
                <x-filament::button
                    href="{{ route('filament.admin.pages.onboarding-wizard-page') }}"
                    tag="a"
                    size="lg"
                    icon="heroicon-o-rocket-launch"
                >
                    Setup starten
                </x-filament::button>
            </div>
        @else
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Onboarding Fortschritt
                        </h3>
                        <p class="text-sm text-gray-600">
                            Schritt {{ $currentStep }} von 7 • {{ $formattedTime }} verstrichen
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold {{ $isWithinTimeLimit ? 'text-green-600' : 'text-orange-600' }}">
                            {{ $progress }}%
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ $isWithinTimeLimit ? 'Im Zeitlimit' : 'Über 5 Minuten' }}
                        </p>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="overflow-hidden h-4 bg-gray-200 rounded-full">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500"
                             style="width: {{ $progress }}%"></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-2">
                    <x-filament::button
                        href="{{ route('filament.admin.pages.onboarding-wizard-page') }}"
                        tag="a"
                        color="primary"
                        size="sm"
                    >
                        Setup fortsetzen
                    </x-filament::button>
                    
                    <div class="text-sm text-gray-500">
                        @if($progress >= 80)
                            Fast geschafft! 🎯
                        @elseif($progress >= 50)
                            Läuft super! 💪
                        @else
                            Weiter so! 🚀
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>