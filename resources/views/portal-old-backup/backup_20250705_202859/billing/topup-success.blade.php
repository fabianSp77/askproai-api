@extends('portal.layouts.app')

@section('title', 'Zahlung erfolgreich')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
            <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Zahlung erfolgreich!</h1>
        
        <p class="text-lg text-gray-600 mb-8">
            Vielen Dank für Ihre Zahlung. Ihr Guthaben wurde erfolgreich aufgeladen.
        </p>
        
        @if(isset($transaction))
            <!-- Transaction Details -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Transaktionsdetails</h2>
                
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Transaktionsnummer:</dt>
                        <dd class="font-medium text-gray-800">{{ $transaction->reference_id }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Betrag:</dt>
                        <dd class="font-medium text-green-600">+{{ number_format($transaction->amount, 2, ',', '.') }} €</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Neues Guthaben:</dt>
                        <dd class="font-medium text-gray-800">{{ number_format($transaction->balance_after, 2, ',', '.') }} €</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Datum:</dt>
                        <dd class="font-medium text-gray-800">{{ $transaction->created_at->format('d.m.Y H:i') }} Uhr</dd>
                    </div>
                </dl>
            </div>
            
            <!-- Email Confirmation -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-8 text-left">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Eine Bestätigung wurde an Ihre E-Mail-Adresse gesendet.
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('business.billing.index') }}" 
               class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h11M9 21V3m0 0L3 9m6-6l6 6"/>
                </svg>
                Zur Übersicht
            </a>
            
            <a href="{{ route('business.billing.transactions') }}" 
               class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Rechnung anzeigen
            </a>
        </div>
    </div>
</div>
@endsection