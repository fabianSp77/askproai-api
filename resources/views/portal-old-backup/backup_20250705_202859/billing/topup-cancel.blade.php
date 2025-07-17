@extends('portal.layouts.app')

@section('title', 'Zahlung abgebrochen')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Cancel Icon -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-gray-100 mb-6">
            <svg class="h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Zahlung abgebrochen</h1>
        
        <p class="text-lg text-gray-600 mb-8">
            Die Zahlung wurde abgebrochen. Ihr Guthaben wurde nicht belastet.
        </p>
        
        <!-- Current Balance Info -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
            <p class="text-gray-700">
                Ihr aktuelles Guthaben beträgt weiterhin:
                <span class="font-semibold text-xl block mt-2">{{ number_format($currentBalance, 2, ',', '.') }} €</span>
            </p>
        </div>
        
        <!-- Help Text -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-8 text-left">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Falls Sie Probleme bei der Zahlung hatten, kontaktieren Sie uns gerne unter
                        <a href="mailto:support@askproai.de" class="font-medium underline">support@askproai.de</a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('business.billing.topup') }}" 
               class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Erneut versuchen
            </a>
            
            <a href="{{ route('business.billing.index') }}" 
               class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück zur Übersicht
            </a>
        </div>
    </div>
</div>
@endsection