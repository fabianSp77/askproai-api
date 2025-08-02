@extends('portal.layouts.unified')

@section('page-title', 'Neuer Termin')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('business.appointments.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Zurück zur Übersicht
            </a>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Neuen Termin erstellen</h3>
            </div>
            
            <form action="{{ route('business.appointments.store') }}" method="POST" class="px-6 py-4 space-y-6">
                @csrf
                
                <!-- Customer Selection -->
                <div>
                    <label for="customer" class="block text-sm font-medium text-gray-700 mb-2">
                        Kunde
                    </label>
                    <select id="customer" name="customer_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Kunde auswählen...</option>
                        <option value="1">Max Mustermann - +49 123 456789</option>
                        <option value="2">Erika Musterfrau - +49 987 654321</option>
                        <option value="new">+ Neuen Kunden anlegen</option>
                    </select>
                </div>

                <!-- Service Selection -->
                <div>
                    <label for="service" class="block text-sm font-medium text-gray-700 mb-2">
                        Service
                    </label>
                    <select id="service" name="service_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Service auswählen...</option>
                        <option value="1">Beratung (60 Min)</option>
                        <option value="2">Erstgespräch (30 Min)</option>
                        <option value="3">Nachuntersuchung (45 Min)</option>
                    </select>
                </div>

                <!-- Date & Time -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                            Datum
                        </label>
                        <input type="date" id="date" name="date" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label for="time" class="block text-sm font-medium text-gray-700 mb-2">
                            Uhrzeit
                        </label>
                        <select id="time" name="time" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Zeit auswählen...</option>
                            <option value="09:00">09:00</option>
                            <option value="09:30">09:30</option>
                            <option value="10:00">10:00</option>
                            <option value="10:30">10:30</option>
                            <option value="11:00">11:00</option>
                            <option value="11:30">11:30</option>
                            <option value="14:00">14:00</option>
                            <option value="14:30">14:30</option>
                            <option value="15:00">15:00</option>
                            <option value="15:30">15:30</option>
                            <option value="16:00">16:00</option>
                            <option value="16:30">16:30</option>
                        </select>
                    </div>
                </div>

                <!-- Staff Selection -->
                <div>
                    <label for="staff" class="block text-sm font-medium text-gray-700 mb-2">
                        Mitarbeiter
                    </label>
                    <select id="staff" name="staff_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Automatisch zuweisen</option>
                        <option value="1">Dr. Schmidt</option>
                        <option value="2">Fr. Müller</option>
                        <option value="3">Hr. Weber</option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notizen
                    </label>
                    <textarea id="notes" name="notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Zusätzliche Informationen zum Termin..."></textarea>
                </div>

                <!-- Reminder Options -->
                <div class="border-t pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Erinnerungen
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="send_email_reminder" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                            <span class="text-sm text-gray-700">E-Mail-Erinnerung (24h vorher)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="send_sms_reminder" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">SMS-Erinnerung (2h vorher)</span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <a href="{{ route('business.appointments.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Abbrechen
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Termin erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection