<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <x-slot name="description">
            {{ $this->getDescription() }}
        </x-slot>

        <div class="space-y-6">
            {{-- Current Balance Display --}}
            @if(auth()->user()->tenant)
                <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Eingezahltes Guthaben</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $this->getCurrentBalance()['balance'] }}€
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Bonus-Guthaben</div>
                            <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                                +{{ $this->getCurrentBalance()['bonus'] }}€
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Gesamt verfügbar</div>
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                {{ $this->getCurrentBalance()['total'] }}€
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Bonus Tiers --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    🎁 Bonus-Staffelung
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->getBonusTiers() as $tier)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition-shadow duration-200
                            @if($tier['name'] === 'Diamond') bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 @endif">

                            <div class="flex items-center justify-between mb-3">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $tier['name'] }}
                                </span>
                                <x-filament::badge :color="$tier['badge_color']" size="lg">
                                    {{ $tier['bonus'] }} Bonus
                                </x-filament::badge>
                            </div>

                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                {{ $tier['range'] }}
                            </div>

                            @if(!empty($tier['examples']))
                                <div class="space-y-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div class="text-xs text-gray-500 dark:text-gray-500 mb-1">Beispiele:</div>
                                    @foreach($tier['examples'] as $example)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ $example['amount'] }}
                                            </span>
                                            <span class="text-success-600 dark:text-success-400 font-medium">
                                                {{ $example['bonus'] }}
                                            </span>
                                            <span class="font-bold text-gray-900 dark:text-white">
                                                = {{ $example['total'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Stripe Payment Info --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            Sichere Zahlung mit Stripe
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                            <p>Wir verwenden Stripe für sichere Zahlungen. Ihre Zahlungsdaten werden verschlüsselt übertragen und niemals auf unseren Servern gespeichert.</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span class="inline-flex items-center">
                                    <svg class="h-8 w-auto" viewBox="0 0 60 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M59.64 14.28h-8.06c.07 1.23.51 1.85 1.64 1.85 1.12 0 1.96-.6 2.09-1.22h3.99c-.23 2.7-2.9 4.42-6.17 4.42-3.77 0-6.4-2.38-6.4-6.07 0-3.68 2.66-6.25 6.3-6.25 3.86 0 6.61 2.46 6.61 6.07v1.2zm-3.93-2.52c-.18-.98-.88-1.46-1.75-1.46-.87 0-1.56.48-1.73 1.46h3.48zm-10.09-4.48v.73c-.6-.61-1.55-.95-2.65-.95-2.78 0-4.98 2.3-4.98 6.16 0 3.86 2.2 6.16 4.98 6.16 1.1 0 2.06-.34 2.65-.96v.49c0 .86 0 1.1.02 1.22h3.78c-.02-.15-.02-.5-.02-1.43V7.28h-3.78zm-1.85 8.9c-1.22 0-2.08-.88-2.08-2.67 0-1.8.86-2.68 2.08-2.68 1.2 0 2.06.89 2.06 2.68 0 1.79-.85 2.67-2.06 2.67zm-6.99-8.9h-3.91v11.72h3.9V7.28zm-1.95-1.55c1.3 0 2.09-.77 2.09-1.8 0-1.02-.8-1.79-2.1-1.79-1.28 0-2.07.77-2.07 1.8 0 1.02.79 1.79 2.08 1.79zM27.42 7.28h-3.58v11.72h3.9v-5.77c0-1.71.85-2.49 2.33-2.49.31 0 .53.02.82.07V7c-.3-.05-.53-.07-.78-.07-1.23 0-2.28.56-2.69 1.55v-1.2zm-7-3.65h-3.9v3.65h-1.74v3.2h1.75v4.88c0 2.66 1.27 3.91 4.27 3.91.62 0 1.18-.07 1.74-.2v-3.33c-.26.05-.53.08-.8.08-.75 0-1.32-.29-1.32-1.17v-4.17h2.12v-3.2H20.4V3.63zm-8.04 8.24c-1.34-.82-2.83-1.22-2.83-2.1 0-.48.4-.75 1.06-.75.98 0 1.99.58 2.49 1.18l1.82-2.42c-.96-.95-2.56-1.68-4.36-1.68-2.52 0-4.41 1.41-4.41 3.6 0 2.42 2.06 3.37 3.6 4.17 1.46.77 2.62 1.24 2.62 2.18 0 .54-.49.85-1.23.85-1.2 0-2.3-.75-2.81-1.63L5.25 18c.82 1.36 2.67 2.27 4.68 2.27 2.84 0 4.75-1.39 4.75-3.73 0-2.52-1.97-3.48-3.32-4.17z" fill="#635BFF"/>
                                    </svg>
                                </span>
                                <span class="text-xs">PCI DSS Level 1 zertifiziert</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Important Notes --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-3">
                    ℹ️ Wichtige Hinweise
                </h3>
                <ul class="space-y-2 text-sm text-amber-700 dark:text-amber-400">
                    @foreach($this->getImportantNotes() as $note)
                        <li class="flex items-start">
                            <span class="flex-shrink-0 mr-2">{{ substr($note, 0, 2) }}</span>
                            <span>{{ substr($note, 3) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- CTA Button --}}
            <div class="text-center pt-4">
                <x-filament::button
                    href="{{ route('filament.admin.resources.balance-topups.create') }}"
                    tag="a"
                    size="lg"
                    icon="heroicon-o-currency-euro"
                >
                    Jetzt Guthaben aufladen
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>