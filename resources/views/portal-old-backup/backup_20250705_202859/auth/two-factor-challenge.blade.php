@extends('portal.layouts.auth')

@section('title', 'Zwei-Faktor-Authentifizierung')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Zwei-Faktor-Authentifizierung
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Geben Sie Ihren Authentifizierungscode ein
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            @if(session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tab Navigation -->
            <div class="mb-6">
                <nav class="flex space-x-4" aria-label="Tabs">
                    <button type="button"
                            onclick="switchTab('authenticator')"
                            id="authenticator-tab"
                            class="tab-button text-indigo-700 bg-indigo-100 px-3 py-2 font-medium text-sm rounded-md">
                        Authenticator App
                    </button>
                    <button type="button"
                            onclick="switchTab('recovery')"
                            id="recovery-tab"
                            class="tab-button text-gray-500 hover:text-gray-700 px-3 py-2 font-medium text-sm rounded-md">
                        Wiederherstellungscode
                    </button>
                </nav>
            </div>

            <!-- Authenticator Code Form -->
            <div id="authenticator-panel" class="tab-panel">
                <form method="POST" action="{{ route('business.two-factor.verify') }}">
                    @csrf
                    
                    <div class="space-y-6">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">
                                Authentifizierungscode
                            </label>
                            <div class="mt-1">
                                <input id="code" name="code" type="text" required autofocus
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('code') border-red-300 @enderror"
                                       placeholder="6-stelliger Code">
                                @error('code')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein
                            </p>
                        </div>

                        <div>
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Verifizieren
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recovery Code Form -->
            <div id="recovery-panel" class="tab-panel hidden">
                <form method="POST" action="{{ route('business.two-factor.verify-recovery') }}">
                    @csrf
                    
                    <div class="space-y-6">
                        <div>
                            <label for="recovery_code" class="block text-sm font-medium text-gray-700">
                                Wiederherstellungscode
                            </label>
                            <div class="mt-1">
                                <input id="recovery_code" name="recovery_code" type="text" required
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('recovery_code') border-red-300 @enderror"
                                       placeholder="xxxxx-xxxxx">
                                @error('recovery_code')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Verwenden Sie einen Ihrer Wiederherstellungscodes
                            </p>
                        </div>

                        <div class="rounded-md bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Jeder Wiederherstellungscode kann nur einmal verwendet werden
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Mit Wiederherstellungscode anmelden
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help Text -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Probleme beim Anmelden?
                <a href="{{ route('business.support') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Support kontaktieren
                </a>
            </p>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Hide all panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.add('hidden');
    });
    
    // Reset all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('text-indigo-700', 'bg-indigo-100');
        button.classList.add('text-gray-500', 'hover:text-gray-700');
    });
    
    // Show selected panel
    document.getElementById(tab + '-panel').classList.remove('hidden');
    
    // Highlight selected tab
    const selectedTab = document.getElementById(tab + '-tab');
    selectedTab.classList.remove('text-gray-500', 'hover:text-gray-700');
    selectedTab.classList.add('text-indigo-700', 'bg-indigo-100');
    
    // Focus the input field
    if (tab === 'authenticator') {
        document.getElementById('code').focus();
    } else {
        document.getElementById('recovery_code').focus();
    }
}
</script>
@endsection