<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Success Hero -->
        <div class="bg-gradient-to-r from-success-500 to-success-600 rounded-2xl p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">🎉 Glückwunsch!</h1>
                    <p class="text-xl opacity-90">Ihr KI-Telefon-System ist einsatzbereit!</p>
                </div>
                <div class="text-6xl">
                    ✅
                </div>
            </div>
        </div>

        <!-- Setup Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="bg-primary-100 dark:bg-primary-900 p-2 rounded-lg">
                        <x-heroicon-o-building-office class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="text-lg font-semibold">Firma & Filiale</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    <strong>{{ $company->name }}</strong><br>
                    {{ $branch->name }} in {{ $branch->city }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="bg-primary-100 dark:bg-primary-900 p-2 rounded-lg">
                        <x-heroicon-o-calendar class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="text-lg font-semibold">Kalender</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    Cal.com verbunden<br>
                    <span class="text-success-600">✓ Event Types importiert</span>
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="bg-primary-100 dark:bg-primary-900 p-2 rounded-lg">
                        <x-heroicon-o-phone class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="text-lg font-semibold">KI-Telefon</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    Agent aktiviert<br>
                    <strong class="text-primary-600">{{ $testPhoneNumber }}</strong>
                </p>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-primary-50 dark:bg-primary-900/20 rounded-xl p-6">
            <h2 class="text-xl font-semibold mb-4">🚀 Die nächsten Schritte:</h2>
            
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="bg-white dark:bg-gray-800 p-2 rounded-full mt-1">
                        <span class="text-lg font-bold text-primary-600">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Test-Anruf durchführen</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Rufen Sie <strong>{{ $testPhoneNumber }}</strong> an und testen Sie Ihren KI-Assistenten.
                        </p>
                        <x-filament::button
                            size="sm"
                            class="mt-2"
                            icon="heroicon-o-phone"
                            onclick="alert('Rufen Sie jetzt {{ $testPhoneNumber }} an!')"
                        >
                            Anruf-Info anzeigen
                        </x-filament::button>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="bg-white dark:bg-gray-800 p-2 rounded-full mt-1">
                        <span class="text-lg font-bold text-primary-600">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Mitarbeiter hinzufügen</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Fügen Sie Ihre Mitarbeiter hinzu und verknüpfen Sie sie mit Services.
                        </p>
                        <x-filament::button
                            href="{{ route('filament.admin.resources.staff.create') }}"
                            tag="a"
                            size="sm"
                            class="mt-2"
                            color="gray"
                            icon="heroicon-o-user-plus"
                        >
                            Mitarbeiter anlegen
                        </x-filament::button>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="bg-white dark:bg-gray-800 p-2 rounded-full mt-1">
                        <span class="text-lg font-bold text-primary-600">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Dashboard erkunden</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Sehen Sie Ihre Anrufe und Termine in Echtzeit.
                        </p>
                        <x-filament::button
                            href="/admin"
                            tag="a"
                            size="sm"
                            class="mt-2"
                            color="gray"
                            icon="heroicon-o-chart-bar"
                        >
                            Zum Dashboard
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-col sm:flex-row gap-4">
            <x-filament::button
                href="/admin"
                tag="a"
                size="lg"
                class="flex-1"
                icon="heroicon-o-home"
            >
                Zum Dashboard
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.calls.index') }}"
                tag="a"
                size="lg"
                color="gray"
                class="flex-1"
                icon="heroicon-o-phone"
            >
                Anrufe anzeigen
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.appointments.index') }}"
                tag="a"
                size="lg"
                color="gray"
                class="flex-1"
                icon="heroicon-o-calendar"
            >
                Termine verwalten
            </x-filament::button>
        </div>

        <!-- Help Section -->
        <div class="text-center py-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400 mb-2">
                Brauchen Sie Hilfe bei der Einrichtung?
            </p>
            <div class="flex justify-center space-x-4">
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
                    📚 Dokumentation
                </a>
                <span class="text-gray-400">•</span>
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
                    💬 Support Chat
                </a>
                <span class="text-gray-400">•</span>
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
                    📧 support@askproai.com
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>