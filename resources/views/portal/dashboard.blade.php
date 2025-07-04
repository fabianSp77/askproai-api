@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Company Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $company->name }} - Business Portal
                </h1>
                <p class="text-gray-600 mt-2">
                    Willkommen im Business Portal
                </p>
            </div>
        </div>
        
        <!-- Prepaid Balance Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Prepaid Guthaben</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Current Balance -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-blue-700">Aktuelles Guthaben</div>
                        <div class="mt-2 text-2xl font-bold text-blue-900">
                            {{ number_format($company->prepaid_balance ?? 0, 2, ',', '.') }} €
                        </div>
                    </div>
                    
                    <!-- Remaining Minutes -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-green-700">Verbleibende Minuten</div>
                        <div class="mt-2 text-2xl font-bold text-green-900">
                            {{ number_format(($company->prepaid_balance ?? 0) / 0.42, 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-green-600 mt-1">bei 0,42€/Min</div>
                    </div>
                    
                    <!-- Last Updated -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-700">Letzte Aktualisierung</div>
                        <div class="mt-2 text-lg font-medium text-gray-900">
                            {{ $company->prepaid_balance_updated_at ? $company->prepaid_balance_updated_at->format('d.m.Y H:i') : 'Noch nie' }}
                        </div>
                    </div>
                </div>
                
                <!-- Reload Button -->
                <div class="mt-6">
                    <a href="{{ route('business.billing.topup') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Guthaben aufladen
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Anrufe heute
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                {{ isset($stats['total_calls_today']) ? $stats['total_calls_today'] : 0 }}
                            </dd>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <a href="{{ route('business.calls.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        Alle Anrufe →
                    </a>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Gesprächsminuten heute
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                {{ isset($stats['total_minutes_today']) ? number_format($stats['total_minutes_today'], 0) : 0 }}
                            </dd>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <span class="text-sm text-gray-600">
                        Kosten: {{ number_format((isset($stats['total_minutes_today']) ? $stats['total_minutes_today'] : 0) * 0.42, 2, ',', '.') }} €
                    </span>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Team Mitglieder
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                {{ method_exists($company, 'portalUsers') ? $company->portalUsers()->count() : 0 }}
                            </dd>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <a href="{{ route('business.team.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        Team verwalten →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection