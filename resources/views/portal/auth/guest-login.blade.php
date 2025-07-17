@extends('portal.layouts.auth')

@section('title', 'Gastzugang - ' . $company->name)

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Anrufdetails anzeigen
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sie wurden eingeladen, die Details eines Anrufs bei {{ $company->name }} einzusehen.
            </p>
        </div>

        <!-- Call Info Card -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Anrufinformationen</h3>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Datum:</dt>
                    <dd class="text-sm text-gray-900">{{ $call->created_at->format('d.m.Y H:i') }} Uhr</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Anrufer:</dt>
                    <dd class="text-sm text-gray-900">{{ $call->extracted_name ?? $call->from_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Dauer:</dt>
                    <dd class="text-sm text-gray-900">{{ gmdate('i:s', $call->duration_sec ?? 0) }}</dd>
                </div>
            </dl>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session('info'))
            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Login Options -->
        <div class="space-y-6">
            <!-- Existing User Login -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Bereits registriert?</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Wenn Sie bereits Zugangsdaten haben, melden Sie sich bitte an.
                </p>
                <a href="{{ route('business.login') }}?return={{ urlencode($returnUrl) }}" 
                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Zur Anmeldung
                </a>
            </div>

            <!-- Request Access -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Zugang anfragen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Fordern Sie Zugang an, um die Anrufdetails einzusehen.
                </p>
                
                <form method="POST" action="{{ route('business.guest.request-access') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="call_id" value="{{ $call->id }}">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ihr Name</label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                               value="{{ old('name') }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">E-Mail-Adresse</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-300 @enderror"
                               value="{{ old('email') }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700">Grund für die Anfrage</label>
                        <textarea name="reason" 
                                  id="reason" 
                                  rows="3" 
                                  required 
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('reason') border-red-300 @enderror"
                                  placeholder="Bitte beschreiben Sie kurz, warum Sie Zugang zu diesen Anrufdetails benötigen...">{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Zugang anfragen
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-sm text-gray-500">
                Nach der Genehmigung durch einen Administrator erhalten Sie Ihre Zugangsdaten per E-Mail.
            </p>
        </div>
    </div>
</div>
@endsection