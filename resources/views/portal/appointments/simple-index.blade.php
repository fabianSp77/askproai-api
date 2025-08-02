@extends('portal.layouts.unified')

@section('page-title', 'Termine')

@section('header-actions')
<a href="{{ route('business.appointments.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
    <i class="fas fa-plus mr-2"></i>
    Neuer Termin
</a>
@endsection

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                        <input type="date" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Alle Status</option>
                            <option value="scheduled">Geplant</option>
                            <option value="confirmed">Bestätigt</option>
                            <option value="completed">Abgeschlossen</option>
                            <option value="cancelled">Abgesagt</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mitarbeiter</label>
                        <select class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Alle Mitarbeiter</option>
                            <option value="1">Dr. Schmidt</option>
                            <option value="2">Fr. Müller</option>
                            <option value="3">Hr. Weber</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                        <input type="text" placeholder="Kunde, Service..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar View Toggle -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Termine Übersicht</h3>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded-md text-sm font-medium">
                            <i class="fas fa-list mr-1"></i> Liste
                        </button>
                        <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                            <i class="fas fa-calendar mr-1"></i> Kalender
                        </button>
                    </div>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="divide-y divide-gray-200">
                <!-- Today's Appointments -->
                <div class="px-6 py-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Heute - {{ now()->format('d.m.Y') }}</h4>
                    
                    <!-- Appointment Item -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="text-lg font-medium text-gray-900">09:00 - 10:00</span>
                                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Bestätigt
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-900">Max Mustermann</p>
                                <p class="text-sm text-gray-500">Beratung • Dr. Schmidt • Hauptfiliale</p>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('business.appointments.show', 1) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('business.appointments.edit', 1) }}" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="text-lg font-medium text-gray-900">11:00 - 11:30</span>
                                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Geplant
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-900">Erika Musterfrau</p>
                                <p class="text-sm text-gray-500">Erstgespräch • Fr. Müller • Hauptfiliale</p>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('business.appointments.show', 2) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('business.appointments.edit', 2) }}" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tomorrow's Appointments -->
                <div class="px-6 py-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Morgen - {{ now()->addDay()->format('d.m.Y') }}</h4>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="text-lg font-medium text-gray-900">14:00 - 14:45</span>
                                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Bestätigt
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-900">Thomas Schmidt</p>
                                <p class="text-sm text-gray-500">Nachuntersuchung • Hr. Weber • Filiale Nord</p>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('business.appointments.show', 3) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('business.appointments.edit', 3) }}" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Zeige <span class="font-medium">1</span> bis <span class="font-medium">3</span> von <span class="font-medium">3</span> Ergebnissen
            </div>
            <div class="flex space-x-2">
                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50" disabled>
                    Zurück
                </button>
                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50" disabled>
                    Weiter
                </button>
            </div>
        </div>
    </div>
</div>
@endsection