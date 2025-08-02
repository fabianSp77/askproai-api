@extends('portal.layouts.alpine-app')

@section('title', 'Dashboard')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600">Willkommen zurück, {{ auth()->user()->name }}!</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Today's Appointments -->
        <div x-data="statsCard" 
             data-format="number"
             x-init="
                 value = {{ $stats['todayAppointments'] ?? 0 }};
                 previousValue = {{ $stats['yesterdayAppointments'] ?? 0 }};
                 Alpine.store('portal').on('appointments.updated', (data) => {
                     value = data.todayCount;
                     animateValue();
                 });
             "
             class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Heutige Termine</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" x-text="formattedValue"></div>
                                <div x-show="trend != 0" 
                                     :class="trendPositive ? 'text-green-600' : 'text-red-600'"
                                     class="ml-2 flex items-baseline text-sm font-semibold">
                                    <svg :class="trendPositive ? 'text-green-500' : 'text-red-500'" 
                                         class="self-center flex-shrink-0 h-5 w-5" 
                                         fill="currentColor" 
                                         viewBox="0 0 20 20">
                                        <path x-show="trendPositive" fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                        <path x-show="!trendPositive" fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span x-text="Math.abs(trend) + '%'"></span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <button @click="refresh()" class="font-medium text-blue-600 hover:text-blue-500">
                        Details anzeigen
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Total Calls -->
        <div x-data="statsCard" 
             data-format="number"
             x-init="
                 value = {{ $stats['totalCalls'] ?? 0 }};
                 previousValue = {{ $stats['previousCalls'] ?? 0 }};
                 Alpine.store('portal').on('calls.updated', (data) => {
                     value = data.totalCount;
                     animateValue();
                 });
             "
             class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Anrufe (30 Tage)</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" x-text="formattedValue"></div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('portal.calls.index') }}" class="font-medium text-green-600 hover:text-green-500">
                        Alle anzeigen
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Average Call Duration -->
        <div x-data="statsCard" 
             data-format="duration"
             x-init="
                 value = {{ $stats['avgCallDuration'] ?? 0 }};
                 previousValue = {{ $stats['previousAvgDuration'] ?? 0 }};
             "
             class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ø Anrufdauer</dt>
                            <dd class="text-2xl font-semibold text-gray-900" x-text="formattedValue"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue -->
        <div x-data="statsCard" 
             data-format="currency"
             x-init="
                 value = {{ $stats['monthlyRevenue'] ?? 0 }};
                 previousValue = {{ $stats['previousMonthRevenue'] ?? 0 }};
             "
             class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Monatsumsatz</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" x-text="formattedValue"></div>
                                <div x-show="trend != 0" 
                                     :class="trendPositive ? 'text-green-600' : 'text-red-600'"
                                     class="ml-2 flex items-baseline text-sm font-semibold">
                                    <span x-text="(trendPositive ? '+' : '') + trend + '%'"></span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('portal.billing.index') }}" class="font-medium text-purple-600 hover:text-purple-500">
                        Zur Abrechnung
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity & Quick Actions -->
    <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Recent Activity -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Letzte Aktivitäten</h3>
                <div class="mt-5">
                    <div x-data="{ 
                            activities: [],
                            loading: true,
                            async init() {
                                try {
                                    const response = await Alpine.store('portal').get('/activities');
                                    this.activities = response.data;
                                } catch (error) {
                                    console.error('Failed to load activities:', error);
                                } finally {
                                    this.loading = false;
                                }
                                
                                // Listen for real-time updates
                                Alpine.store('portal').on('activity.created', (activity) => {
                                    this.activities.unshift(activity);
                                    if (this.activities.length > 10) {
                                        this.activities.pop();
                                    }
                                });
                            }
                         }" 
                         class="flow-root">
                        <div x-show="loading" class="text-center py-4">
                            <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        
                        <ul x-show="!loading" class="-mb-8">
                            <template x-for="(activity, index) in activities" :key="activity.id">
                                <li>
                                    <div class="relative pb-8">
                                        <span x-show="index !== activities.length - 1" 
                                              class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                                                      :class="{
                                                          'bg-blue-500': activity.type === 'appointment',
                                                          'bg-green-500': activity.type === 'call',
                                                          'bg-purple-500': activity.type === 'customer',
                                                          'bg-gray-400': !['appointment', 'call', 'customer'].includes(activity.type)
                                                      }">
                                                    <svg x-show="activity.type === 'appointment'" class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <svg x-show="activity.type === 'call'" class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                    </svg>
                                                    <svg x-show="activity.type === 'customer'" class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-900" x-html="activity.description"></p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <time :datetime="activity.created_at" x-text="Alpine.store('portal').formatTimeAgo(activity.created_at)"></time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        
                        <div x-show="!loading && activities.length === 0" class="text-center py-4 text-gray-500">
                            Keine Aktivitäten vorhanden
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Schnellaktionen</h3>
                <div class="mt-5 grid grid-cols-1 gap-3">
                    <!-- Create Appointment Modal -->
                    <div x-data="modal">
                        <button @click="open = true" 
                                class="w-full inline-flex items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Neuen Termin erstellen
                        </button>
                        
                        <!-- Modal Content -->
                        <div x-show="open" 
                             x-transition:enter="ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed z-10 inset-0 overflow-y-auto">
                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="open" 
                                     @click="open = false"
                                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                                
                                <div x-show="open"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     @click.away="open = false"
                                     class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                    <form action="{{ route('portal.appointments.store') }}" method="POST">
                                        @csrf
                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                            <h3 class="text-lg font-medium text-gray-900 mb-4">Neuen Termin erstellen</h3>
                                            
                                            <!-- Quick appointment form fields -->
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Kunde</label>
                                                    <select name="customer_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                        <option value="">Kunde auswählen...</option>
                                                        @foreach($customers ?? [] as $customer)
                                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Datum</label>
                                                    <div x-data="datepicker" class="mt-1">
                                                        <input type="hidden" name="date" x-ref="input">
                                                        <input type="text" 
                                                               x-model="displayValue"
                                                               @click="toggle()"
                                                               readonly
                                                               placeholder="Datum wählen..."
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Uhrzeit</label>
                                                    <input type="time" name="time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Service</label>
                                                    <select name="service_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                        <option value="">Service auswählen...</option>
                                                        @foreach($services ?? [] as $service)
                                                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration }} Min.)</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                Termin erstellen
                                            </button>
                                            <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                Abbrechen
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="{{ route('portal.customers.create') }}" 
                       class="inline-flex items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Neuen Kunden anlegen
                    </a>
                    
                    <a href="{{ route('portal.team.invite') }}" 
                       class="inline-flex items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Mitarbeiter einladen
                    </a>
                    
                    <a href="{{ route('portal.calls.index') }}" 
                       class="inline-flex items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Anrufliste öffnen
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="mt-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Heutige Termine</h3>
                    <a href="{{ route('portal.appointments.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Alle anzeigen →
                    </a>
                </div>
                
                <div x-data="{ 
                        appointments: [],
                        loading: true,
                        async init() {
                            try {
                                const response = await Alpine.store('portal').get('/appointments/today');
                                this.appointments = response.data;
                            } catch (error) {
                                console.error('Failed to load appointments:', error);
                            } finally {
                                this.loading = false;
                            }
                            
                            // Listen for updates
                            Alpine.store('portal').on('appointments.updated', async () => {
                                const response = await Alpine.store('portal').get('/appointments/today');
                                this.appointments = response.data;
                            });
                        }
                     }">
                    <div x-show="loading" class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <div x-show="!loading && appointments.length > 0" class="overflow-hidden">
                        <ul class="divide-y divide-gray-200">
                            <template x-for="appointment in appointments" :key="appointment.id">
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-500">
                                                <span class="text-sm font-medium leading-none text-white" x-text="appointment.customer_name.charAt(0).toUpperCase()"></span>
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="appointment.customer_name"></p>
                                            <p class="text-sm text-gray-500" x-text="appointment.service_name"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900" x-text="appointment.time"></p>
                                            <p class="text-sm text-gray-500" x-text="appointment.duration + ' Min.'"></p>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-yellow-100 text-yellow-800': appointment.status === 'scheduled',
                                                      'bg-green-100 text-green-800': appointment.status === 'confirmed',
                                                      'bg-blue-100 text-blue-800': appointment.status === 'completed',
                                                      'bg-red-100 text-red-800': appointment.status === 'cancelled'
                                                  }">
                                                <span x-text="appointment.status_label"></span>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                    
                    <div x-show="!loading && appointments.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2">Keine Termine für heute</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // Initialize real-time dashboard updates
    const portal = Alpine.store('portal');
    
    if (portal.echo) {
        // Listen for dashboard-specific updates
        portal.echo.private(`dashboard.${portal.company.id}`)
            .listen('DashboardUpdated', (e) => {
                console.log('Dashboard update received:', e);
                // Trigger updates for specific components
                portal.emit('dashboard.updated', e.data);
            });
    }
});
</script>
@endpush