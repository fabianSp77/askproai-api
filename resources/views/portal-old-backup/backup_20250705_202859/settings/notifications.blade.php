@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Benachrichtigungseinstellungen</h1>
            <p class="mt-1 text-sm text-gray-600">
                Verwalten Sie, wie und wann Sie Benachrichtigungen erhalten
            </p>
        </div>

        <form method="POST" action="{{ route('business.settings.notifications.update') }}">
            @csrf

            <!-- Notification Channels -->
            <div class="bg-white shadow sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Benachrichtigungskanäle
                    </h3>
                    <div class="space-y-4">
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input id="channel_email" name="channels[]" value="email" type="checkbox" 
                                       @if(in_array('email', $preferences['channels'] ?? ['email'])) checked @endif
                                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="channel_email" class="font-medium text-gray-700">E-Mail</label>
                                <p class="text-gray-500">Benachrichtigungen per E-Mail erhalten</p>
                            </div>
                        </div>

                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input id="channel_sms" name="channels[]" value="sms" type="checkbox" 
                                       @if(in_array('sms', $preferences['channels'] ?? [])) checked @endif
                                       disabled
                                       class="h-4 w-4 text-gray-300 border-gray-300 rounded cursor-not-allowed">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="channel_sms" class="font-medium text-gray-400">SMS (Demnächst)</label>
                                <p class="text-gray-400">SMS-Benachrichtigungen werden in Kürze verfügbar sein</p>
                            </div>
                        </div>

                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input id="channel_push" name="channels[]" value="push" type="checkbox" 
                                       @if(in_array('push', $preferences['channels'] ?? [])) checked @endif
                                       disabled
                                       class="h-4 w-4 text-gray-300 border-gray-300 rounded cursor-not-allowed">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="channel_push" class="font-medium text-gray-400">Push (Demnächst)</label>
                                <p class="text-gray-400">Browser-Push-Benachrichtigungen werden in Kürze verfügbar sein</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Types -->
            <div class="bg-white shadow sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Benachrichtigungstypen
                    </h3>
                    
                    <!-- Calls -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Anrufe</h4>
                        <div class="space-y-3">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="calls_new" name="types[calls][]" value="new_call" type="checkbox" 
                                           @if(in_array('new_call', $preferences['types']['calls'] ?? [])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="calls_new" class="font-medium text-gray-700">Neue Anrufe</label>
                                    <p class="text-gray-500">Benachrichtigung bei eingehenden Anrufen</p>
                                </div>
                            </div>

                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="calls_assigned" name="types[calls][]" value="call_assigned" type="checkbox" 
                                           @if(in_array('call_assigned', $preferences['types']['calls'] ?? [])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="calls_assigned" class="font-medium text-gray-700">Anrufzuweisungen</label>
                                    <p class="text-gray-500">Wenn Ihnen ein Anruf zugewiesen wird</p>
                                </div>
                            </div>

                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="calls_summary" name="types[calls][]" value="daily_summary" type="checkbox" 
                                           @if(in_array('daily_summary', $preferences['types']['calls'] ?? ['daily_summary'])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="calls_summary" class="font-medium text-gray-700">Tägliche Zusammenfassung</label>
                                    <p class="text-gray-500">Tägliche Übersicht aller Anrufe</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments -->
                    @if($user->company->needsAppointmentBooking())
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Termine</h4>
                        <div class="space-y-3">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="appointments_reminder" name="types[appointments][]" value="reminder_24h" type="checkbox" 
                                           @if(in_array('reminder_24h', $preferences['types']['appointments'] ?? ['reminder_24h'])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="appointments_reminder" class="font-medium text-gray-700">24h Erinnerung</label>
                                    <p class="text-gray-500">Erinnerung 24 Stunden vor Terminen</p>
                                </div>
                            </div>

                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="appointments_changes" name="types[appointments][]" value="changes" type="checkbox" 
                                           @if(in_array('changes', $preferences['types']['appointments'] ?? [])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="appointments_changes" class="font-medium text-gray-700">Terminänderungen</label>
                                    <p class="text-gray-500">Bei Änderungen oder Absagen</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Billing -->
                    @if($user->canViewBilling())
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Abrechnung</h4>
                        <div class="space-y-3">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="billing_invoice" name="types[billing][]" value="new_invoice" type="checkbox" 
                                           @if(in_array('new_invoice', $preferences['types']['billing'] ?? ['new_invoice'])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="billing_invoice" class="font-medium text-gray-700">Neue Rechnungen</label>
                                    <p class="text-gray-500">Wenn eine neue Rechnung verfügbar ist</p>
                                </div>
                            </div>

                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="billing_low_balance" name="types[billing][]" value="low_balance" type="checkbox" 
                                           @if(in_array('low_balance', $preferences['types']['billing'] ?? [])) checked @endif
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="billing_low_balance" class="font-medium text-gray-700">Niedriger Kontostand</label>
                                    <p class="text-gray-500">Warnung bei niedrigem Guthaben</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Notification Schedule -->
            <div class="bg-white shadow sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Zeitplan
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="frequency" class="block text-sm font-medium text-gray-700">
                                Häufigkeit der Zusammenfassungen
                            </label>
                            <select id="frequency" name="frequency" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="realtime" @if(($preferences['frequency'] ?? 'daily') === 'realtime') selected @endif>Echtzeit</option>
                                <option value="daily" @if(($preferences['frequency'] ?? 'daily') === 'daily') selected @endif>Täglich</option>
                                <option value="weekly" @if(($preferences['frequency'] ?? 'daily') === 'weekly') selected @endif>Wöchentlich</option>
                            </select>
                        </div>

                        <div>
                            <label for="time" class="block text-sm font-medium text-gray-700">
                                Uhrzeit für Zusammenfassungen
                            </label>
                            <input type="time" id="time" name="time" 
                                   value="{{ $preferences['time'] ?? '09:00' }}"
                                   class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Einstellungen speichern
                </button>
            </div>
        </form>
    </div>
</div>
@endsection