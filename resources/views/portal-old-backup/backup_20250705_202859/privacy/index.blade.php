@extends('portal.layouts.app')

@section('title', __('Datenschutz-Einstellungen'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Datenschutz-Einstellungen') }}</h1>

    <!-- Cookie Consent Settings -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Cookie-Einstellungen') }}</h2>
        </div>
        <div class="p-6">
            <form action="{{ route('portal.privacy.cookie-consent') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    @foreach($cookieCategories as $key => $category)
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" 
                                       name="{{ $key }}_cookies"
                                       id="{{ $key }}_cookies"
                                       value="1"
                                       @if($key === 'necessary') checked disabled @endif
                                       @if($cookieConsent && data_get($cookieConsent, $key . '_cookies')) checked @endif
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded @if($key === 'necessary') opacity-50 cursor-not-allowed @endif">
                            </div>
                            <div class="ml-3 text-sm flex-1">
                                <label for="{{ $key }}_cookies" class="font-medium text-gray-700 @if($key !== 'necessary') cursor-pointer @endif">
                                    {{ $category['name'] }}
                                    @if($category['required'])
                                        <span class="text-xs text-gray-500">({{ __('Erforderlich') }})</span>
                                    @endif
                                </label>
                                <p class="text-gray-500">{{ $category['description'] }}</p>
                                
                                @if(isset($category['cookies']))
                                    <details class="mt-2">
                                        <summary class="text-xs text-blue-600 cursor-pointer hover:text-blue-700">
                                            {{ __('Verwendete Cookies anzeigen') }}
                                        </summary>
                                        <ul class="mt-2 text-xs text-gray-600 list-disc list-inside">
                                            @foreach($category['cookies'] as $cookieName => $cookieDesc)
                                                <li><strong>{{ $cookieName }}:</strong> {{ $cookieDesc }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ __('Einstellungen speichern') }}
                    </button>
                    
                    @if($cookieConsent && $cookieConsent->isActive())
                        <form action="{{ route('portal.privacy.cookie-consent.withdraw') }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                {{ __('Alle Einwilligungen zurückziehen') }}
                            </button>
                        </form>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Personal Data Management -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Ihre persönlichen Daten') }}</h2>
        </div>
        <div class="p-6">
            <p class="text-gray-600 mb-6">
                {{ __('Gemäß der Datenschutz-Grundverordnung (DSGVO) haben Sie das Recht, Ihre persönlichen Daten einzusehen, zu exportieren oder deren Löschung zu beantragen.') }}
            </p>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Data Export -->
                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-900 mb-2">{{ __('Datenexport') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Laden Sie eine Kopie aller Ihrer bei uns gespeicherten Daten herunter.') }}
                    </p>
                    <form action="{{ route('portal.privacy.request-export') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            {{ __('Daten exportieren') }}
                        </button>
                    </form>
                </div>

                <!-- Data Deletion -->
                <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                    <h3 class="font-medium text-gray-900 mb-2">{{ __('Datenlöschung') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Beantragen Sie die vollständige Löschung Ihrer Daten. Dieser Vorgang kann nicht rückgängig gemacht werden.') }}
                    </p>
                    <button onclick="document.getElementById('deletion-modal').showModal()" 
                            class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{ __('Löschung beantragen') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- GDPR Requests History -->
    @if($gdprRequests->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Ihre Datenschutzanfragen') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Typ') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Datum') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Aktionen') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($gdprRequests as $request)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ __('gdpr.request_type.' . $request->type) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $request->requested_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $request->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $request->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $request->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $request->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ __('gdpr.request_status.' . $request->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($request->type === 'export' && $request->status === 'completed' && $request->export_file_path)
                                <a href="{{ route('portal.privacy.download-export', $request) }}" 
                                   class="text-blue-600 hover:text-blue-900">
                                    {{ __('Herunterladen') }}
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $gdprRequests->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Data Deletion Modal -->
<dialog id="deletion-modal" class="p-0 rounded-lg shadow-xl">
    <div class="bg-white rounded-lg max-w-md">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Datenlöschung beantragen') }}</h3>
            
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">{{ __('Wichtiger Hinweis') }}</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ __('Die Löschung Ihrer Daten ist endgültig und kann nicht rückgängig gemacht werden. Sie verlieren den Zugang zu Ihrem Konto und allen damit verbundenen Diensten.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('portal.privacy.request-deletion') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('Grund für die Löschung (optional)') }}
                    </label>
                    <textarea name="reason" id="reason" rows="3" 
                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                </div>

                <div class="mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="confirm" required
                               class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-600">
                            {{ __('Ich verstehe, dass die Löschung meiner Daten endgültig ist und nicht rückgängig gemacht werden kann.') }}
                        </span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('deletion-modal').close()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        {{ __('Abbrechen') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        {{ __('Löschung beantragen') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</dialog>
@endsection