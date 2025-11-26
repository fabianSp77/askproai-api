{{-- Welcome Banner Component - Shows user their role and permissions --}}
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-lg p-6 mb-6 shadow-sm"
     x-data="{ show: true, closed: localStorage.getItem('welcome_banner_closed') === 'true' }"
     x-show="!closed"
     x-transition>

    <div class="flex items-start">
        <div class="flex-shrink-0">
            <div class="h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center">
                <i class="fas fa-user-check text-white text-xl"></i>
            </div>
        </div>

        <div class="ml-4 flex-1">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    👋 Willkommen, {{ auth()->user()->name }}!
                </h3>
                <button @click="closed = true; localStorage.setItem('welcome_banner_closed', 'true')"
                        class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <p class="mt-2 text-sm text-gray-700">
                Ihr Kundenkonto wurde erfolgreich erstellt. Sie sind jetzt als
                <strong class="text-blue-700">{{ auth()->user()->role->name }}</strong> angemeldet.
            </p>

            {{-- Role-specific information --}}
            <div class="mt-4 bg-white rounded-lg p-4 border border-blue-100">
                <h4 class="text-sm font-semibold text-gray-800 mb-2">
                    📋 Ihre Berechtigungen:
                </h4>

                @if(auth()->user()->role->name === 'viewer')
                    <div class="space-y-2">
                        <p class="text-sm text-gray-700">
                            <strong>👁️ Viewer (Betrachter)</strong> – Nur Leserechte
                        </p>
                        <ul class="text-sm text-gray-600 space-y-1 ml-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Ihre Termine jederzeit einsehen</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Termindetails und Historie anzeigen</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Übersicht über vergangene und zukünftige Termine</span>
                            </li>
                            <li class="flex items-start text-gray-400">
                                <i class="fas fa-times text-gray-400 mt-1 mr-2"></i>
                                <span><em>Termine buchen, verschieben oder stornieren (nicht verfügbar)</em></span>
                            </li>
                        </ul>
                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded text-xs text-gray-700">
                            <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                            <strong>Hinweis:</strong> Zum Ändern von Terminen kontaktieren Sie bitte direkt
                            {{ auth()->user()->company->name }} per Telefon oder E-Mail.
                        </div>
                    </div>

                @elseif(auth()->user()->role->name === 'operator')
                    <div class="space-y-2">
                        <p class="text-sm text-gray-700">
                            <strong>⚙️ Operator (Bearbeiter)</strong> – Operative Tätigkeiten
                        </p>
                        <ul class="text-sm text-gray-600 space-y-1 ml-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Ihre Termine jederzeit einsehen</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Neue Termine online buchen</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Termine flexibel verschieben</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Termine bei Bedarf stornieren</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Alternative Terminvorschläge erhalten</span>
                            </li>
                        </ul>
                    </div>

                @elseif(auth()->user()->role->name === 'manager')
                    <div class="space-y-2">
                        <p class="text-sm text-gray-700">
                            <strong>👔 Manager (Verwalter)</strong> – Management-Rechte
                        </p>
                        <ul class="text-sm text-gray-600 space-y-1 ml-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Vollständige Terminverwaltung</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Termine ansehen, buchen, verschieben und stornieren</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Erweiterte Terminübersicht und Auswertungen</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Zugriff auf alle verfügbaren Funktionen</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Management-Dashboard mit Statistiken</span>
                            </li>
                        </ul>
                    </div>

                @else
                    <div class="space-y-2">
                        <p class="text-sm text-gray-700">
                            <strong>{{ auth()->user()->role->name }}</strong>
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ auth()->user()->role->description ?? 'Kundenportal-Zugang' }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex items-center space-x-4 text-sm">
                <a href="{{ route('customer-portal.appointments.index') }}"
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Zu meinen Terminen
                </a>
                <span class="text-gray-300">|</span>
                <a href="#help"
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-question-circle mr-2"></i>
                    Hilfe & FAQ
                </a>
            </div>
        </div>
    </div>
</div>
