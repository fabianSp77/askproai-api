@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Teammitglied einladen</h1>
            <p class="mt-1 text-sm text-gray-600">
                Laden Sie neue Mitglieder in Ihr Team ein
            </p>
        </div>

        <!-- Invite Form -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="{{ route('business.team.invite.send') }}">
                    @csrf
                    
                    <!-- Email -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            E-Mail-Adresse
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="mitarbeiter@beispiel.de">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Name -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               required
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="Max Mustermann">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Role -->
                    <div class="mb-6">
                        <label for="role" class="block text-sm font-medium text-gray-700">
                            Rolle
                        </label>
                        <select id="role" 
                                name="role" 
                                required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Rolle auswählen</option>
                            @foreach($availableRoles as $role => $label)
                                <option value="{{ $role }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Permissions Info -->
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Rollenberechtigungen</h4>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="role-permissions" data-role="admin" style="display: none;">
                                <p class="font-medium">Administrator</p>
                                <ul class="list-disc list-inside ml-2">
                                    <li>Vollzugriff auf alle Funktionen</li>
                                    <li>Team verwalten</li>
                                    <li>Abrechnung und Zahlungen</li>
                                    <li>Firmenkonfiguration</li>
                                </ul>
                            </div>
                            <div class="role-permissions" data-role="manager" style="display: none;">
                                <p class="font-medium">Manager</p>
                                <ul class="list-disc list-inside ml-2">
                                    <li>Anrufe und Termine verwalten</li>
                                    <li>Team-Analysen einsehen</li>
                                    <li>Berichte erstellen</li>
                                    <li>Keine Abrechnungsfunktionen</li>
                                </ul>
                            </div>
                            <div class="role-permissions" data-role="agent" style="display: none;">
                                <p class="font-medium">Mitarbeiter</p>
                                <ul class="list-disc list-inside ml-2">
                                    <li>Eigene Anrufe einsehen</li>
                                    <li>Eigene Termine verwalten</li>
                                    <li>Persönliche Analysen</li>
                                    <li>Eingeschränkter Zugriff</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Message (Optional) -->
                    <div class="mb-6">
                        <label for="message" class="block text-sm font-medium text-gray-700">
                            Persönliche Nachricht (optional)
                        </label>
                        <textarea id="message" 
                                  name="message" 
                                  rows="3"
                                  class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                  placeholder="Willkommen im Team! Ich freue mich auf die Zusammenarbeit..."></textarea>
                        @error('message')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('business.team.index') }}" 
                           class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </a>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Einladung senden
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Der eingeladene Benutzer erhält eine E-Mail mit einem Link zur Registrierung. 
                        Die Einladung ist 7 Tage gültig.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show role permissions when role is selected
    document.getElementById('role').addEventListener('change', function() {
        // Hide all permission descriptions
        document.querySelectorAll('.role-permissions').forEach(function(el) {
            el.style.display = 'none';
        });
        
        // Show the selected role's permissions
        if (this.value) {
            const selectedPermissions = document.querySelector('.role-permissions[data-role="' + this.value + '"]');
            if (selectedPermissions) {
                selectedPermissions.style.display = 'block';
            }
        }
    });
</script>
@endsection