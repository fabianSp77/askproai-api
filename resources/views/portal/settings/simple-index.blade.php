@extends('portal.layouts.unified')

@section('page-title', 'Einstellungen')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Navigation -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg">
                    <nav class="space-y-1 p-4">
                        <a href="#profile" class="bg-blue-50 text-blue-700 group rounded-md px-3 py-2 flex items-center text-sm font-medium">
                            <i class="fas fa-user mr-3 text-blue-500"></i>
                            Profil
                        </a>
                        <a href="#security" class="text-gray-700 hover:bg-gray-50 group rounded-md px-3 py-2 flex items-center text-sm font-medium">
                            <i class="fas fa-lock mr-3 text-gray-400"></i>
                            Sicherheit
                        </a>
                        <a href="#notifications" class="text-gray-700 hover:bg-gray-50 group rounded-md px-3 py-2 flex items-center text-sm font-medium">
                            <i class="fas fa-bell mr-3 text-gray-400"></i>
                            Benachrichtigungen
                        </a>
                        <a href="#company" class="text-gray-700 hover:bg-gray-50 group rounded-md px-3 py-2 flex items-center text-sm font-medium">
                            <i class="fas fa-building mr-3 text-gray-400"></i>
                            Unternehmen
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Settings -->
                <div id="profile" class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Profil-Einstellungen</h3>
                    </div>
                    <form action="{{ route('business.settings.profile.update') }}" method="POST" class="px-6 py-4 space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vorname</label>
                                <input type="text" name="first_name" value="{{ $user->first_name ?? 'Max' }}" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nachname</label>
                                <input type="text" name="last_name" value="{{ $user->last_name ?? 'Mustermann' }}" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                            <input type="email" name="email" value="{{ $user->email }}" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input type="tel" name="phone" value="{{ $user->phone ?? '+49 123 456789' }}" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Änderungen speichern
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Settings -->
                <div id="security" class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Sicherheit</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6">
                        <!-- Password Change -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Passwort ändern</h4>
                            <form action="{{ route('business.settings.password.update') }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Aktuelles Passwort</label>
                                    <input type="password" name="current_password" 
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Neues Passwort</label>
                                    <input type="password" name="new_password" 
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Neues Passwort bestätigen</label>
                                    <input type="password" name="new_password_confirmation" 
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                                    Passwort ändern
                                </button>
                            </form>
                        </div>

                        <!-- 2FA Settings -->
                        <div class="border-t pt-6">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Zwei-Faktor-Authentifizierung</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-3">
                                    Erhöhen Sie die Sicherheit Ihres Kontos mit der Zwei-Faktor-Authentifizierung.
                                </p>
                                <form action="{{ route('business.settings.2fa.enable') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                        <i class="fas fa-shield-alt mr-2"></i>
                                        2FA aktivieren
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div id="notifications" class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Benachrichtigungen</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" checked class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="text-sm font-medium text-gray-900">E-Mail-Benachrichtigungen</p>
                                <p class="text-sm text-gray-500">Erhalten Sie E-Mails über neue Termine und Änderungen</p>
                            </div>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="text-sm font-medium text-gray-900">SMS-Benachrichtigungen</p>
                                <p class="text-sm text-gray-500">Erhalten Sie SMS für wichtige Updates</p>
                            </div>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" checked class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Browser-Benachrichtigungen</p>
                                <p class="text-sm text-gray-500">Push-Benachrichtigungen im Browser anzeigen</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection