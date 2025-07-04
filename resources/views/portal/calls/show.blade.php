@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('business.calls.index') }}" 
               class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Zurück zur Übersicht
            </a>
        </div>

        <!-- Call Header -->
        <div class="bg-white shadow sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:flex sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Anruf Details
                        </h3>
                        <div class="mt-2 max-w-xl text-sm text-gray-500">
                            <p>Anruf von {{ $call->phone_number }} am {{ $call->created_at ? $call->created_at->format('d.m.Y H:i') : '-' }}</p>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex-shrink-0 sm:flex sm:items-center">
                        @php
                            $status = optional($call->callPortalData)->status ?? 'new';
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold leading-5
                            @if($status === 'completed') bg-green-100 text-green-800
                            @elseif($status === 'new') bg-blue-100 text-blue-800
                            @elseif($status === 'in_progress') bg-yellow-100 text-yellow-800
                            @elseif($status === 'callback_scheduled') bg-purple-100 text-purple-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Call Information -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Anrufinformationen
                        </h3>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Telefonnummer</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $call->phone_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dauer</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($call->duration_sec)
                                        {{ gmdate('H:i:s', $call->duration_sec) }}
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Datum & Zeit</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $call->created_at ? $call->created_at->format('d.m.Y H:i:s') : '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Filiale</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ optional($call->branch)->name ?? '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Zugewiesen an</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($call->callPortalData && $call->callPortalData->assignedTo)
                                        {{ $call->callPortalData->assignedTo->name }}
                                    @else
                                        <span class="text-gray-500">Nicht zugewiesen</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Retell Call ID</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono text-xs">
                                    {{ $call->retell_call_id ?? '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Transcript -->
                @if($call->transcript)
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Gesprächsverlauf
                        </h3>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3 max-h-96 overflow-y-auto">
                            @php
                                $transcript = is_string($call->transcript) ? json_decode($call->transcript, true) : $call->transcript;
                            @endphp
                            @if(is_array($transcript))
                                @foreach($transcript as $message)
                                    <div class="flex {{ $message['role'] === 'agent' ? 'justify-start' : 'justify-end' }}">
                                        <div class="max-w-xs lg:max-w-md {{ $message['role'] === 'agent' ? 'bg-white' : 'bg-blue-100' }} rounded-lg px-4 py-2 shadow">
                                            <p class="text-sm font-medium {{ $message['role'] === 'agent' ? 'text-gray-900' : 'text-blue-900' }} mb-1">
                                                {{ $message['role'] === 'agent' ? 'Agent' : 'Kunde' }}
                                            </p>
                                            <p class="text-sm text-gray-700">{{ $message['content'] ?? '' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-500">Kein Transkript verfügbar</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Summary -->
                @if($call->summary)
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Zusammenfassung
                        </h3>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $call->summary }}</p>
                    </div>
                </div>
                @endif

                <!-- Internal Notes -->
                @if($call->callPortalData && $call->callPortalData->internal_notes)
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Interne Notizen
                        </h3>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $call->callPortalData->internal_notes }}</p>
                    </div>
                </div>
                @endif

                <!-- Call Notes -->
                @if($call->callNotes && $call->callNotes->count() > 0)
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Notizen
                        </h3>
                        <div class="space-y-4">
                            @foreach($call->callNotes as $note)
                                <div class="border-l-4 border-gray-200 pl-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ optional($note->user)->name ?? 'System' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $note->created_at ? $note->created_at->format('d.m.Y H:i') : '' }}
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $note->content }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Customer Information -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Kundeninformationen
                        </h3>
                        <dl class="space-y-3">
                            @if($call->customer)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $call->customer->name }}</dd>
                                </div>
                                @if($call->customer->email)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $call->customer->email }}</dd>
                                </div>
                                @endif
                                @if($call->customer->phone)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $call->customer->phone }}</dd>
                                </div>
                                @endif
                            @else
                                @if($call->extracted_name || $call->extracted_email)
                                    @if($call->extracted_name)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Name (erkannt)</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $call->extracted_name }}</dd>
                                    </div>
                                    @endif
                                    @if($call->extracted_email)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">E-Mail (erkannt)</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $call->extracted_email }}</dd>
                                    </div>
                                    @endif
                                @else
                                    <p class="text-sm text-gray-500">Kein Kunde zugeordnet</p>
                                @endif
                            @endif
                            
                            @if($call->reason_for_visit)
                            <div class="pt-3 border-t border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Anrufgrund</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $call->reason_for_visit }}</dd>
                            </div>
                            @endif
                            
                            @if($call->insurance_type || $call->insurance_company || $call->health_insurance_company)
                            <div class="pt-3 border-t border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Versicherung</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($call->insurance_type)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $call->insurance_type }}
                                        </span>
                                    @endif
                                    @if($call->insurance_company || $call->health_insurance_company)
                                        <span class="ml-1">{{ $call->insurance_company ?? $call->health_insurance_company }}</span>
                                    @endif
                                    @if($call->versicherungsstatus)
                                        <span class="ml-1 text-gray-600">({{ $call->versicherungsstatus }})</span>
                                    @endif
                                </dd>
                            </div>
                            @endif
                            
                            @if($call->urgency_level)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dringlichkeit</dt>
                                <dd class="mt-1">
                                    @php
                                        $urgencyLevel = strtolower($call->urgency_level);
                                        $urgencyColor = match($urgencyLevel) {
                                            'high', 'hoch' => 'red',
                                            'medium', 'mittel' => 'yellow',
                                            'low', 'niedrig' => 'gray',
                                            default => 'gray'
                                        };
                                        $urgencyText = match($urgencyLevel) {
                                            'high' => 'Hoch',
                                            'medium' => 'Mittel',
                                            'low' => 'Niedrig',
                                            'hoch' => 'Hoch',
                                            'mittel' => 'Mittel',
                                            'niedrig' => 'Niedrig',
                                            default => ucfirst($call->urgency_level)
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $urgencyColor }}-100 text-{{ $urgencyColor }}-800">
                                        {{ $urgencyText }}
                                    </span>
                                </dd>
                            </div>
                            @endif
                            
                            @if($call->appointment_requested || $call->appointment_made)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Terminstatus</dt>
                                <dd class="mt-1 flex gap-2">
                                    @if($call->appointment_requested)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Angefragt
                                        </span>
                                    @endif
                                    @if($call->appointment_made)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Gebucht
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            @endif
                        </dl>
                        
                        @if($call->customer && $call->customer->appointments && $call->customer->appointments->count() > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Letzte Termine</h4>
                            <ul class="space-y-2">
                                @foreach($call->customer->appointments->take(3) as $appointment)
                                <li class="text-sm text-gray-600">
                                    {{ optional($appointment->starts_at)->format('d.m.Y H:i') ?? '-' }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
                
                @if(isset($call->metadata['customer_data']) && !empty($call->metadata['customer_data']))
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Kundendaten erfolgreich gesammelt</strong>
                                @if(isset($call->metadata['collection_timestamp']))
                                    <span class="text-xs ml-2">{{ \Carbon\Carbon::parse($call->metadata['collection_timestamp'])->format('d.m.Y H:i') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Gesammelte Kundendaten
                        </h3>
                        @php
                            $customerData = $call->metadata['customer_data'];
                        @endphp
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @if(!empty($customerData['full_name']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $customerData['full_name'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['company']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Firma</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['company'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['customer_number']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Kundennummer</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $customerData['customer_number'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['phone_primary']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Haupttelefon</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['phone_primary'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['phone_secondary']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Zweittelefon</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['phone_secondary'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['email']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:{{ $customerData['email'] }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $customerData['email'] }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['request']))
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Anliegen</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        {{ $customerData['request'] }}
                                    </div>
                                </dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['notes']))
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Zusätzliche Notizen</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['notes'] }}</dd>
                            </div>
                            @endif
                            
                            @if(isset($customerData['consent']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Datenspeicherung zugestimmt</dt>
                                <dd class="mt-1">
                                    @if($customerData['consent'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Ja
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Nein
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
                @elseif($call->customer_data_backup && !empty($call->customer_data_backup))
                <!-- Fallback to backup field if metadata is empty -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Gesammelte Kundendaten (Backup)
                        </h3>
                        @php
                            $customerData = $call->customer_data_backup;
                        @endphp
                        <dl class="space-y-3">
                            @foreach($customerData as $key => $value)
                                @if($value && $key !== 'collected_at')
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ str_replace('_', ' ', ucfirst($key)) }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $value }}</dd>
                                </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                </div>
                @endif
                
                @if($call->custom_analysis_data)
                <div class="bg-white shadow sm:rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Erweiterte Analysedaten
                        </h3>
                        @php
                            $customData = is_string($call->custom_analysis_data) ? json_decode($call->custom_analysis_data, true) : $call->custom_analysis_data;
                        @endphp
                        @if($customData && is_array($customData))
                            <dl class="space-y-3">
                                @foreach($customData as $key => $value)
                                    @if($value && !in_array($key, ['customer_data_backup', 'backup_timestamp']))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ str_replace('_', ' ', ucfirst($key)) }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            @if(is_array($value))
                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                            @elseif(is_bool($value))
                                                {{ $value ? 'Ja' : 'Nein' }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </dd>
                                    </div>
                                    @endif
                                @endforeach
                            </dl>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Aktionen
                        </h3>
                        <div class="space-y-3">
                            <!-- Update Status -->
                            <form method="POST" action="{{ route('business.calls.update-status', $call->id) }}">
                                @csrf
                                @method('PATCH')
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                    Status ändern
                                </label>
                                <select name="status" id="status" 
                                        onchange="this.form.submit()"
                                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    @php
                                        $currentStatus = optional($call->callPortalData)->status ?? 'new';
                                    @endphp
                                    <option value="new" {{ $currentStatus === 'new' ? 'selected' : '' }}>Neu</option>
                                    <option value="in_progress" {{ $currentStatus === 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                                    <option value="callback_scheduled" {{ $currentStatus === 'callback_scheduled' ? 'selected' : '' }}>Rückruf geplant</option>
                                    <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                                </select>
                            </form>

                            <!-- Assign Call -->
                            @if(session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('calls.edit_all')))
                            <form method="POST" action="{{ route('business.calls.assign', $call->id) }}">
                                @csrf
                                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                                    Zuweisen an
                                </label>
                                <select name="assigned_to" id="assigned_to" 
                                        onchange="this.form.submit()"
                                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">Nicht zugewiesen</option>
                                    @foreach($teamMembers as $member)
                                        <option value="{{ $member->id }}" 
                                            {{ optional($call->callPortalData)->assigned_to == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                            @endif

                            <!-- Add Note -->
                            <div>
                                <button type="button" 
                                        onclick="document.getElementById('add-note-form').classList.toggle('hidden')"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    Notiz hinzufügen
                                </button>
                            </div>

                            <!-- Schedule Callback -->
                            @if(optional($call->callPortalData)->status !== 'completed')
                            <div>
                                <button type="button" 
                                        onclick="document.getElementById('schedule-callback-form').classList.toggle('hidden')"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Rückruf planen
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Call History -->
                @if($customerCallHistory && $customerCallHistory->count() > 0)
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Anrufhistorie
                        </h3>
                        <ul class="divide-y divide-gray-200">
                            @foreach($customerCallHistory as $historyCall)
                            <li class="py-3">
                                <a href="{{ route('business.calls.show', $historyCall->id) }}" 
                                   class="flex items-center justify-between hover:bg-gray-50 -mx-2 px-2 py-1 rounded">
                                    <div class="flex flex-col">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $historyCall->created_at ? $historyCall->created_at->format('d.m.Y H:i') : '-' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ gmdate('i:s', $historyCall->duration_sec ?? 0) }} - 
                                            {{ ucfirst(optional($historyCall->callPortalData)->status ?? 'new') }}
                                        </p>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Hidden Forms -->
        <!-- Add Note Form -->
        <div id="add-note-form" class="hidden mt-6 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="{{ route('business.calls.add-note', $call->id) }}">
                    @csrf
                    <div>
                        <label for="note_content" class="block text-sm font-medium text-gray-700">
                            Neue Notiz
                        </label>
                        <textarea name="content" id="note_content" rows="3" 
                                  class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md"
                                  placeholder="Ihre Notiz hier eingeben..."></textarea>
                    </div>
                    <div class="mt-3 flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('add-note-form').classList.add('hidden')"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </button>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Notiz speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedule Callback Form -->
        <div id="schedule-callback-form" class="hidden mt-6 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="{{ route('business.calls.schedule-callback', $call->id) }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="callback_date" class="block text-sm font-medium text-gray-700">
                                Datum
                            </label>
                            <input type="date" name="callback_date" id="callback_date" 
                                   min="{{ now()->addDay()->format('Y-m-d') }}"
                                   class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label for="callback_time" class="block text-sm font-medium text-gray-700">
                                Uhrzeit
                            </label>
                            <input type="time" name="callback_time" id="callback_time" 
                                   class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="callback_notes" class="block text-sm font-medium text-gray-700">
                            Notizen (optional)
                        </label>
                        <textarea name="callback_notes" id="callback_notes" rows="2" 
                                  class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md"
                                  placeholder="Notizen zum Rückruf..."></textarea>
                    </div>
                    <div class="mt-3 flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('schedule-callback-form').classList.add('hidden')"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </button>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Rückruf planen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection