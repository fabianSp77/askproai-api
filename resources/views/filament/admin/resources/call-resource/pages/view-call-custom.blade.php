<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}" class="fi min-h-screen">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Anruf #{{ $record->id }} - Details</title>
    
    @php
        $panel = filament()->getCurrentPanel();
    @endphp
    
    @foreach($panel->getFontHtml() as $font)
        {!! $font !!}
    @endforeach
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    @filamentStyles
    @filamentScripts
    @livewireStyles
    @livewireScripts
</head>
<body class="fi-body fi-panel-admin min-h-screen bg-gray-50 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white">
    <div class="fi-layout flex min-h-screen w-full flex-row-reverse overflow-x-clip">
        <div class="fi-main-ctn w-screen flex-1 flex-col">
            <main class="fi-main mx-auto h-full w-full px-4 md:px-6 lg:px-8 max-w-7xl">
                <div class="fi-page-wrapper py-8">
                    <div class="fi-page">
                        {{-- Page Header --}}
                        <div class="fi-page-header-wrapper">
                            <div class="fi-page-header">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                                            Anruf #{{ $record->id }} - Details
                                        </h1>
                                        <p class="fi-page-subheading mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            @if($record->customer)
                                                {{ $record->customer->name }} • 
                                            @endif
                                            {{ $record->created_at?->format('d.m.Y H:i:s') ?? 'Datum unbekannt' }}
                                        </p>
                                    </div>
                                    
                                    <div class="fi-page-header-actions flex gap-3">
                                        <a href="{{ \App\Filament\Admin\Resources\CallResource::getUrl('index') }}"
                                           class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20">
                                            <svg class="fi-btn-icon transition duration-75 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                            </svg>
                                            <span class="fi-btn-label">Zurück zur Liste</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Page Content --}}
                        <div class="fi-page-content mt-6">
                            <div class="grid grid-cols-1 gap-y-6">
                                {{-- Call Details Section --}}
                                @include('filament.admin.resources.call-resource.partials.call-details', ['record' => $record])
                                
                                {{-- Audio Player Section --}}
                                @if($record->recording_url || $record->audio_url || ($record->webhook_data['recording_url'] ?? null))
                                    @include('filament.admin.resources.call-resource.partials.audio-player', [
                                        'recording_url' => $record->recording_url ?? $record->audio_url ?? ($record->webhook_data['recording_url'] ?? null),
                                        'call_id' => $record->id,
                                        'duration' => $record->duration_sec
                                    ])
                                @endif
                                
                                {{-- Analysis Section --}}
                                @if($record->sentiment || $record->appointment_made || $record->appointment_requested)
                                    @include('filament.admin.resources.call-resource.partials.analysis', ['record' => $record])
                                @endif
                                
                                {{-- Customer Section --}}
                                @include('filament.admin.resources.call-resource.partials.customer', ['record' => $record])
                                
                                {{-- Notes Section --}}
                                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                                    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                                        <div class="fi-section-header-heading flex-1">
                                            <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                                Notizen
                                            </h3>
                                            <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                                                Interne Notizen und Kommentare zu diesem Anruf
                                            </p>
                                        </div>
                                    </div>
                                    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                                        <div class="fi-section-content p-6">
                                            @livewire('call-notes-component', ['call' => $record])
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Transcript Section --}}
                                @if($record->transcript)
                                    @include('filament.admin.resources.call-resource.partials.transcript', ['transcript' => $record->transcript])
                                @endif
                                
                                {{-- Technical Details Section --}}
                                @include('filament.admin.resources.call-resource.partials.technical-details', ['record' => $record])
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>