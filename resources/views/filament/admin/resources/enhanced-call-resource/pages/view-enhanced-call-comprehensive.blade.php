<x-filament-panels::page>
    <div class="fi-in-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="p-6">
            {{-- Enhanced Call View with Complete June Features + Modern Improvements --}}
            
            {{-- Header Section with Status Indicators --}}
            <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-8 h-8 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            Anruf #{{ $record->id }}
                            @if($record->first_visit)
                                <span class="ml-3 px-3 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                                    NEUKUNDE
                                </span>
                            @endif
                        </h1>
                        <div class="mt-2 flex flex-wrap gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $record->created_at->format('d.m.Y H:i:s') }}
                            </span>
                            @if($record->call_id)
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                    </svg>
                                    {{ $record->call_id }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Quick Status Badges --}}
                    <div class="mt-4 lg:mt-0 flex flex-wrap gap-2">
                        @if($record->call_successful)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✓ Erfolgreich
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                ✗ Fehlgeschlagen
                            </span>
                        @endif
                        
                        @if($record->appointment_requested)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                📅 Termin angefragt
                            </span>
                        @endif
                        
                        @if($record->urgency_level)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                {{ $record->urgency_level == 'high' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                ⚡ {{ ucfirst($record->urgency_level) }} Priorität
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                
                {{-- Left Column - Call Details & Analytics --}}
                <div class="xl:col-span-2 space-y-6">
                    
                    {{-- Call Recording & Media Section --}}
                    @if($record->audio_url || $record->recording_url)
                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800 rounded-lg p-6 border border-purple-200 dark:border-gray-700">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path>
                                </svg>
                                Anrufaufzeichnung
                            </h2>
                            
                            <audio controls class="w-full mb-4">
                                <source src="{{ $record->audio_url ?? $record->recording_url }}" type="audio/wav">
                                Ihr Browser unterstützt keine Audio-Wiedergabe.
                            </audio>
                            
                            <div class="flex flex-wrap gap-2">
                                @if($record->public_log_url)
                                    <a href="{{ $record->public_log_url }}" target="_blank" class="inline-flex items-center px-3 py-1 text-sm font-medium text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Öffentliches Log
                                    </a>
                                @endif
                                
                                <a href="{{ $record->audio_url ?? $record->recording_url }}" download class="inline-flex items-center px-3 py-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Comprehensive Call Information --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                Anrufdetails
                            </h2>
                            
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Basic Call Info --}}
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Von Nummer</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                        <a href="tel:{{ $record->from_number }}" class="hover:text-blue-600">{{ $record->from_number ?? 'Unbekannt' }}</a>
                                    </dd>
                                </div>
                                
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">An Nummer</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->to_number ?? 'Unbekannt' }}</dd>
                                </div>
                                
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dauer</dt>
                                    <dd class="mt-1">
                                        @if($record->duration_sec)
                                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ gmdate('i:s', $record->duration_sec) }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">({{ $record->duration_sec }}s)</span>
                                        @else
                                            <span class="text-sm text-gray-500">N/A</span>
                                        @endif
                                    </dd>
                                </div>
                                
                                {{-- Advanced Info --}}
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sprache</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        @if($record->detected_language)
                                            <span class="font-semibold">{{ strtoupper($record->detected_language) }}</span>
                                            @if($record->language_confidence)
                                                <span class="text-xs text-gray-500 ml-1">({{ round($record->language_confidence * 100) }}%)</span>
                                            @endif
                                        @else
                                            <span class="text-gray-500">Nicht erkannt</span>
                                        @endif
                                    </dd>
                                </div>
                                
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trennungsgrund</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ $record->disconnect_reason ?? $record->disconnection_reason ?? 'Unbekannt' }}
                                    </dd>
                                </div>
                                
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sentiment</dt>
                                    <dd class="mt-1">
                                        @php
                                            $sentiment = $record->sentiment ?? 'neutral';
                                            $sentimentColors = [
                                                'positive' => 'text-green-600 bg-green-100',
                                                'neutral' => 'text-gray-600 bg-gray-100',
                                                'negative' => 'text-red-600 bg-red-100'
                                            ];
                                            $sentimentIcons = [
                                                'positive' => '😊',
                                                'neutral' => '😐',
                                                'negative' => '😟'
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $sentimentColors[$sentiment] ?? 'text-gray-600 bg-gray-100' }}">
                                            {{ $sentimentIcons[$sentiment] ?? '❓' }} {{ ucfirst($sentiment) }}
                                            @if($record->sentiment_score)
                                                ({{ round($record->sentiment_score, 2) }})
                                            @endif
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    {{-- Customer Extracted Data --}}
                    @if($record->name || $record->email || $record->telefonnummer || $record->datum_termin)
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-800 rounded-lg p-6 border border-green-200 dark:border-gray-700">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                                Extrahierte Terminanfrage
                            </h2>
                            
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @if($record->name || $record->extracted_name)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $record->name ?? $record->extracted_name ?? 'Nicht angegeben' }}
                                        </dd>
                                    </div>
                                @endif
                                
                                @if($record->email || $record->extracted_email)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">E-Mail</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            <a href="mailto:{{ $record->email ?? $record->extracted_email }}" class="hover:text-blue-600">
                                                {{ $record->email ?? $record->extracted_email }}
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                                
                                @if($record->datum_termin || $record->extracted_date)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gewünschtes Datum</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $record->datum_termin ?? $record->extracted_date }}
                                        </dd>
                                    </div>
                                @endif
                                
                                @if($record->uhrzeit_termin || $record->extracted_time)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gewünschte Uhrzeit</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $record->uhrzeit_termin ?? $record->extracted_time }}
                                        </dd>
                                    </div>
                                @endif
                                
                                @if($record->dienstleistung)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dienstleistung</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->dienstleistung }}</dd>
                                    </div>
                                @endif
                                
                                @if($record->grund || $record->reason_for_visit)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grund des Besuchs</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->grund ?? $record->reason_for_visit }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif
                    
                    {{-- Healthcare Information --}}
                    @if($record->health_insurance_company || $record->insurance_company || $record->rezeptstatus || $record->behandlung_dauer)
                        <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-gray-800 dark:to-gray-800 rounded-lg p-6 border border-red-200 dark:border-gray-700">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                </svg>
                                Gesundheitsinformationen
                            </h2>
                            
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @if($record->health_insurance_company || $record->insurance_company)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Krankenkasse</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $record->health_insurance_company ?? $record->insurance_company }}
                                        </dd>
                                    </div>
                                @endif
                                
                                @if($record->insurance_type)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Versicherungstyp</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->insurance_type }}</dd>
                                    </div>
                                @endif
                                
                                @if($record->versicherungsstatus)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Versicherungsstatus</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                {{ $record->versicherungsstatus == 'verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($record->versicherungsstatus) }}
                                            </span>
                                        </dd>
                                    </div>
                                @endif
                                
                                @if($record->rezeptstatus)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rezeptstatus</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->rezeptstatus }}</dd>
                                    </div>
                                @endif
                                
                                @if($record->behandlung_dauer)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Behandlungsdauer</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->behandlung_dauer }}</dd>
                                    </div>
                                @endif
                                
                                @if($record->haustiere_name)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Haustier</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">🐾 {{ $record->haustiere_name }}</dd>
                                    </div>
                                @endif
                            </dl>
                            
                            @if($record->notiz)
                                <div class="mt-4 pt-4 border-t border-red-200 dark:border-gray-700">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notizen</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->notiz }}</dd>
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    {{-- Cost Breakdown & Analytics --}}
                    @if($record->cost || $record->cost_cents || $record->analysis)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                    </svg>
                                    Kosten & Analyse
                                </h2>
                                
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {{-- Cost Summary --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Kostenübersicht</h3>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Gesamtkosten:</span>
                                                <span class="text-lg font-bold text-gray-900 dark:text-white">
                                                    €{{ number_format(($record->cost ?? ($record->cost_cents / 100) ?? 0), 2, ',', '.') }}
                                                </span>
                                            </div>
                                            
                                            @if($record->analysis && isset($record->analysis['cost_breakdown']))
                                                @php $breakdown = $record->analysis['cost_breakdown']; @endphp
                                                @if(isset($breakdown['product_costs']))
                                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 space-y-1">
                                                        @foreach($breakdown['product_costs'] as $cost)
                                                            <div class="flex justify-between text-xs">
                                                                <span class="text-gray-600 dark:text-gray-400">{{ $cost['product'] }}:</span>
                                                                <span class="text-gray-900 dark:text-white">€{{ number_format($cost['cost_euros'] ?? 0, 4, ',', '.') }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    
                                    {{-- Performance Metrics --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Performance</h3>
                                        @if($record->analysis && isset($record->analysis['latency']))
                                            @php $latency = $record->analysis['latency']; @endphp
                                            <div class="space-y-2">
                                                @if(isset($latency['e2e']))
                                                    <div class="flex justify-between text-sm">
                                                        <span class="text-gray-600 dark:text-gray-400">End-to-End Latenz:</span>
                                                        <span class="font-mono text-gray-900 dark:text-white">{{ $latency['e2e']['p50'] ?? 'N/A' }}ms</span>
                                                    </div>
                                                @endif
                                                @if(isset($latency['llm']))
                                                    <div class="flex justify-between text-sm">
                                                        <span class="text-gray-600 dark:text-gray-400">LLM Latenz:</span>
                                                        <span class="font-mono text-gray-900 dark:text-white">{{ $latency['llm']['p50'] ?? 'N/A' }}ms</span>
                                                    </div>
                                                @endif
                                                @if(isset($latency['tts']))
                                                    <div class="flex justify-between text-sm">
                                                        <span class="text-gray-600 dark:text-gray-400">TTS Latenz:</span>
                                                        <span class="font-mono text-gray-900 dark:text-white">{{ $latency['tts']['p50'] ?? 'N/A' }}ms</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Token Usage --}}
                                @if($record->analysis && isset($record->analysis['llm_usage']))
                                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Token-Nutzung</h3>
                                        <div class="flex items-center space-x-4">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                Durchschnitt: <span class="font-mono font-semibold">{{ round($record->analysis['llm_usage']['average'] ?? 0) }}</span> Tokens
                                            </span>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                Anfragen: <span class="font-mono font-semibold">{{ $record->analysis['llm_usage']['num_requests'] ?? 0 }}</span>
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    {{-- Summary & Transcript --}}
                    @if($record->summary)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Zusammenfassung
                                </h2>
                                <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    {{ $record->summary }}
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($record->transcript)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Vollständiges Transkript
                                </h2>
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                                    <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono leading-relaxed">{{ $record->transcript }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                {{-- Right Column - Customer & Related Information --}}
                <div class="xl:col-span-1 space-y-6">
                    
                    {{-- Customer Profile --}}
                    @if($record->customer)
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800 rounded-lg p-6 border border-indigo-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                                Kundenprofil
                            </h3>
                            
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        {{ strtoupper(substr($record->customer->name ?? 'U', 0, 2)) }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $record->customer->name ?? 'Unbekannt' }}
                                    </p>
                                    @if($record->customer->email)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ $record->customer->email }}
                                        </p>
                                    @endif
                                    @if($record->customer->phone)
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ $record->customer->phone }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Customer Journey Stats --}}
                            <div class="mt-4 pt-4 border-t border-indigo-200 dark:border-gray-700 grid grid-cols-2 gap-3">
                                @if($record->no_show_count !== null)
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->no_show_count }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">No-Shows</p>
                                    </div>
                                @endif
                                @if($record->reschedule_count !== null)
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->reschedule_count }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Umbuchungen</p>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="mt-4">
                                <a href="/admin/customers/{{ $record->customer->id }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    Kundenprofil anzeigen
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Agent Information --}}
                    @if($record->agent_id || $record->agent_name)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                </svg>
                                KI-Agent
                            </h3>
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $record->agent_name ?? 'Agent ' . $record->agent_id }}
                                </p>
                                @if($record->agent_version)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Version: {{ $record->agent_version }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    {{-- Related Appointment --}}
                    @if($record->appointment)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                                Verknüpfter Termin
                            </h3>
                            <div class="space-y-2">
                                @if($record->appointment->service)
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $record->appointment->service->name }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $record->appointment->starts_at ? $record->appointment->starts_at->format('d.m.Y H:i') : 'Zeit nicht festgelegt' }}
                                </p>
                                @if($record->calcom_booking_id)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Cal.com ID: {{ $record->calcom_booking_id }}
                                    </p>
                                @endif
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <a href="/admin/appointments/{{ $record->appointment->id }}" class="inline-flex items-center text-sm font-medium text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">
                                        Termin anzeigen
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Quick Actions --}}
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schnellaktionen</h3>
                        <div class="space-y-2">
                            <a href="/admin/enhanced-calls/{{ $record->id }}/edit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Bearbeiten
                            </a>
                            
                            @if(!$record->appointment && $record->appointment_requested)
                                <button type="button" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Termin erstellen
                                </button>
                            @endif
                            
                            @if($record->customer)
                                <a href="/admin/customers/{{ $record->customer->id }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Kunde anzeigen
                                </a>
                            @endif
                            
                            @if($record->audio_url || $record->recording_url)
                                <a href="{{ $record->audio_url ?? $record->recording_url }}" download class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                    Aufnahme herunterladen
                                </a>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Technical Metadata --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            Technische Details
                        </h3>
                        <dl class="space-y-2 text-sm">
                            @if($record->retell_call_id)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Retell ID</dt>
                                    <dd class="font-mono text-xs text-gray-900 dark:text-white break-all">{{ $record->retell_call_id }}</dd>
                                </div>
                            @endif
                            @if($record->conversation_id)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Konversation ID</dt>
                                    <dd class="font-mono text-xs text-gray-900 dark:text-white break-all">{{ $record->conversation_id }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Erstellt</dt>
                                <dd class="text-xs text-gray-900 dark:text-white">{{ $record->created_at->format('d.m.Y H:i:s') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Aktualisiert</dt>
                                <dd class="text-xs text-gray-900 dark:text-white">{{ $record->updated_at->format('d.m.Y H:i:s') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>