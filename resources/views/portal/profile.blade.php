@extends('portal.layouts.app')

@section('title', 'Mein Profil')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Mein Profil</h2>

                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Personal Information Form -->
                <form method="POST" action="{{ route('portal.profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Persönliche Informationen</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="name" 
                                       value="{{ old('name', $customer->name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       required>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">E-Mail-Adresse</label>
                                <input type="email" name="email" id="email" 
                                       value="{{ old('email', $customer->email) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       required>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Telefonnummer</label>
                                <input type="tel" name="phone" id="phone" 
                                       value="{{ old('phone', $customer->phone) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       required>
                            </div>

                            <div>
                                <label for="preferred_language" class="block text-sm font-medium text-gray-700">Bevorzugte Sprache</label>
                                <select name="preferred_language" id="preferred_language" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        required>
                                    <option value="de" {{ old('preferred_language', $customer->preferred_language ?? 'de') == 'de' ? 'selected' : '' }}>Deutsch</option>
                                    <option value="en" {{ old('preferred_language', $customer->preferred_language ?? 'de') == 'en' ? 'selected' : '' }}>English</option>
                                </select>
                            </div>

                            <div>
                                <label for="birthdate" class="block text-sm font-medium text-gray-700">Geburtsdatum</label>
                                <input type="date" name="birthdate" id="birthdate" 
                                       value="{{ old('birthdate', $customer->birthdate?->format('Y-m-d')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Firmeninformationen</h3>
                        
                        <div class="bg-gray-50 p-4 rounded-md">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Firma</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $customer->company->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kunden-ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900">#{{ $customer->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Mitglied seit</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $customer->created_at->format('d.m.Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Bevorzugte Filiale</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $customer->preferred_branch_id ? ($customer->preferredBranch->name ?? 'Nicht gefunden') : 'Keine ausgewählt' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Änderungen speichern
                        </button>
                    </div>
                </form>

                <!-- Password Change Form -->
                <div class="mt-10 border-t border-gray-200 pt-10">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Passwort ändern</h3>
                    
                    <form method="POST" action="{{ route('portal.profile.password') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Aktuelles Passwort</label>
                                <input type="password" name="current_password" id="current_password" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       required>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Neues Passwort</label>
                                <input type="password" name="password" id="password" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       required>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Passwort bestätigen</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       required>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Passwort ändern
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Actions -->
                <div class="mt-10 border-t border-gray-200 pt-10">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Konto-Aktionen</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Benachrichtigungen</h4>
                                <p class="text-sm text-gray-500">E-Mail-Benachrichtigungen für Termine und Updates</p>
                            </div>
                            <span class="text-sm text-gray-500">Immer aktiv</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Zwei-Faktor-Authentifizierung</h4>
                                <p class="text-sm text-gray-500">Zusätzliche Sicherheit für Ihr Konto</p>
                            </div>
                            <a href="{{ route('portal.security.2fa') }}" 
                               class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                Konfigurieren
                            </a>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Konto löschen</h4>
                                <p class="text-sm text-gray-500">Dauerhaft alle Daten entfernen</p>
                            </div>
                            <button type="button" 
                                    onclick="confirmAccountDeletion()"
                                    class="text-sm font-medium text-red-600 hover:text-red-500">
                                Konto löschen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Deletion Modal -->
<div id="deleteAccountModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="{{ route('portal.profile.delete') }}">
                @csrf
                @method('DELETE')
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Konto löschen
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Sind Sie sicher, dass Sie Ihr Konto dauerhaft löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                                </p>
                                <div class="mt-4">
                                    <label for="delete_password" class="block text-sm font-medium text-gray-700">
                                        Geben Sie Ihr Passwort zur Bestätigung ein:
                                    </label>
                                    <input type="password" name="password" id="delete_password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Konto löschen
                    </button>
                    <button type="button" 
                            onclick="closeDeleteModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Abbrechen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmAccountDeletion() {
    document.getElementById('deleteAccountModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteAccountModal').classList.add('hidden');
}
</script>
@endsection