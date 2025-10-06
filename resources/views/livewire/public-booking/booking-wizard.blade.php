<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Online Terminbuchung</h1>
            <p class="mt-2 text-gray-600">Buchen Sie Ihren Wunschtermin in wenigen Schritten</p>
        </div>

        {{-- Progress Bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex-1 @if($i < $totalSteps) pr-8 @endif">
                        <div class="relative">
                            @if($i < $totalSteps)
                                <div class="absolute top-5 w-full h-0.5 @if($i < $currentStep) bg-primary-600 @else bg-gray-300 @endif"></div>
                            @endif
                            <div class="relative flex items-center justify-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    @if($i < $currentStep) bg-primary-600 text-white
                                    @elseif($i === $currentStep) bg-primary-500 text-white ring-4 ring-primary-200
                                    @else bg-gray-300 text-gray-500 @endif">
                                    @if($i < $currentStep)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        {{ $i }}
                                    @endif
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-center @if($i === $currentStep) font-semibold text-gray-900 @else text-gray-500 @endif">
                                @switch($i)
                                    @case(1) Service @break
                                    @case(2) Filiale @break
                                    @case(3) Termin @break
                                    @case(4) Daten @break
                                    @case(5) Bestätigung @break
                                @endswitch
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- Error Messages --}}
        @if(count($errors) > 0)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        @foreach($errors as $error)
                            <p class="text-sm text-red-600">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Success Message --}}
        @if($successMessage)
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-600">{{ $successMessage }}</p>
                </div>
            </div>
        @endif

        {{-- Main Content Card --}}
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-900">{{ $this->getStepTitle() }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $this->getStepDescription() }}</p>
            </div>

            <div class="p-6" wire:loading.class="opacity-50">
                {{-- Step 1: Service Selection --}}
                @if($currentStep === 1)
                    <div class="space-y-4">
                        <div class="mb-4">
                            <input type="text"
                                   wire:model.debounce.300ms="serviceSearch"
                                   placeholder="Service suchen..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($services as $service)
                                <div wire:click="selectService({{ $service['id'] }})"
                                     class="border rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow
                                            @if($selectedServiceId === $service['id']) ring-2 ring-primary-500 bg-primary-50 @endif">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ $service['name'] }}</h3>
                                            <p class="text-sm text-gray-600 mt-1">{{ $service['description'] ?? '' }}</p>
                                            <div class="flex items-center mt-2 space-x-4">
                                                <span class="text-sm text-gray-500">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ $service['duration_minutes'] }} Min.
                                                </span>
                                                <span class="text-sm text-gray-500">
                                                    {{ $service['category'] ?? 'Allgemein' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-gray-900">
                                                €{{ number_format($service['price'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Step 2: Branch & Staff Selection --}}
                @if($currentStep === 2)
                    <div class="space-y-6">
                        {{-- Branch Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Filiale wählen</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($branches as $branch)
                                    <div wire:click="$set('selectedBranchId', {{ $branch['id'] }})"
                                         class="border rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow
                                                @if($selectedBranchId === $branch['id']) ring-2 ring-primary-500 bg-primary-50 @endif">
                                        <h3 class="font-semibold text-gray-900">{{ $branch['name'] }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">{{ $branch['address'] ?? '' }}</p>
                                        @if(isset($branch['phone']))
                                            <p class="text-sm text-gray-500 mt-1">📞 {{ $branch['phone'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Staff Selection --}}
                        @if($selectedBranchId && count($availableStaff) > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mitarbeiter wählen (optional)</label>
                                <div class="space-y-2">
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50
                                                  @if($anyStaff) ring-2 ring-primary-500 bg-primary-50 @endif">
                                        <input type="radio"
                                               wire:model="anyStaff"
                                               value="1"
                                               class="mr-3 text-primary-600 focus:ring-primary-500">
                                        <span class="text-gray-900">Kein bestimmter Mitarbeiter</span>
                                    </label>

                                    @foreach($availableStaff as $staff)
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50
                                                      @if(!$anyStaff && $selectedStaffId === $staff['id']) ring-2 ring-primary-500 bg-primary-50 @endif">
                                            <input type="radio"
                                                   wire:model="selectedStaffId"
                                                   wire:click="$set('anyStaff', false)"
                                                   value="{{ $staff['id'] }}"
                                                   class="mr-3 text-primary-600 focus:ring-primary-500">
                                            <div>
                                                <span class="text-gray-900 font-medium">{{ $staff['name'] }}</span>
                                                @if(isset($staff['specialization']))
                                                    <span class="text-sm text-gray-600 ml-2">{{ $staff['specialization'] }}</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 3: Date & Time Selection --}}
                @if($currentStep === 3)
                    <div class="space-y-6">
                        {{-- Date Selection --}}
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Datum wählen</label>
                            <input type="date"
                                   id="date"
                                   wire:model="selectedDate"
                                   min="{{ today()->format('Y-m-d') }}"
                                   max="{{ today()->addDays(90)->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>

                        {{-- Time Slot Grid --}}
                        @if($selectedDate)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Uhrzeit wählen</label>

                                <div wire:loading wire:target="selectedDate" class="text-center py-8">
                                    <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">Verfügbare Zeiten werden geladen...</p>
                                </div>

                                <div wire:loading.remove wire:target="selectedDate">
                                    @if(count($availableSlots) > 0)
                                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                                            @foreach($availableSlots as $slot)
                                                <button type="button"
                                                        wire:click="selectTimeSlot('{{ $slot['time'] }}')"
                                                        class="p-2 text-sm border rounded-lg hover:shadow-md transition-all
                                                               @if($selectedTimeSlot === $slot['time'])
                                                                   bg-primary-600 text-white border-primary-600
                                                               @else
                                                                   bg-white text-gray-900 border-gray-300 hover:border-primary-500
                                                               @endif">
                                                    <div class="font-medium">{{ $slot['display'] ?? $slot['time'] }}</div>
                                                    @if(isset($slot['staff_name']) && !$anyStaff)
                                                        <div class="text-xs mt-1 @if($selectedTimeSlot === $slot['time']) text-primary-100 @else text-gray-500 @endif">
                                                            {{ $slot['staff_name'] }}
                                                        </div>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-gray-600">Leider sind an diesem Tag keine Termine verfügbar.</p>
                                            <p class="text-sm text-gray-500 mt-1">Bitte wählen Sie ein anderes Datum.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 4: Customer Information --}}
                @if($currentStep === 4)
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input type="text"
                                       id="name"
                                       wire:model.defer="customerName"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                       required>
                                @error('customerName')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-Mail *</label>
                                <input type="email"
                                       id="email"
                                       wire:model.defer="customerEmail"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                       required>
                                @error('customerEmail')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input type="tel"
                                   id="phone"
                                   wire:model.defer="customerPhone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            @error('customerPhone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Anmerkungen</label>
                            <textarea id="notes"
                                      wire:model.defer="customerNotes"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                        </div>

                        <div class="space-y-3 border-t pt-4">
                            <label class="flex items-start">
                                <input type="checkbox"
                                       wire:model.defer="acceptsMarketing"
                                       class="mt-1 mr-3 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-gray-600">
                                    Ich möchte über Neuigkeiten und Angebote per E-Mail informiert werden.
                                </span>
                            </label>

                            <label class="flex items-start">
                                <input type="checkbox"
                                       wire:model.defer="gdprConsent"
                                       class="mt-1 mr-3 text-primary-600 focus:ring-primary-500"
                                       required>
                                <span class="text-sm text-gray-600">
                                    Ich habe die <a href="#" class="text-primary-600 underline">Datenschutzerklärung</a> gelesen und akzeptiere diese. *
                                </span>
                            </label>
                            @error('gdprConsent')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                {{-- Step 5: Confirmation --}}
                @if($currentStep === 5)
                    <div class="space-y-6">
                        @if($appointment)
                            <div class="text-center py-8">
                                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Termin erfolgreich gebucht!</h3>
                                <p class="text-gray-600 mb-6">Vielen Dank für Ihre Buchung. Sie erhalten in Kürze eine Bestätigungs-E-Mail.</p>

                                <div class="bg-gray-50 rounded-lg p-6 text-left max-w-md mx-auto">
                                    <h4 class="font-semibold text-gray-900 mb-3">Ihre Buchungsdetails:</h4>
                                    <dl class="space-y-2">
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Bestätigungscode:</dt>
                                            <dd class="font-mono font-bold text-primary-600">{{ $confirmationCode }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Service:</dt>
                                            <dd class="font-medium">{{ $selectedService?->name }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Datum:</dt>
                                            <dd class="font-medium">{{ \Carbon\Carbon::parse($appointment->starts_at)->format('d.m.Y') }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Uhrzeit:</dt>
                                            <dd class="font-medium">{{ \Carbon\Carbon::parse($appointment->starts_at)->format('H:i') }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Mitarbeiter:</dt>
                                            <dd class="font-medium">{{ $selectedStaff?->name ?? 'Wird zugeteilt' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Filiale:</dt>
                                            <dd class="font-medium">{{ $selectedBranch?->name }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="mt-8 space-y-3">
                                    <a href="{{ url('/') }}"
                                       class="inline-block px-6 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors">
                                        Neue Buchung
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Footer Navigation --}}
            @if($currentStep < 5)
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between">
                    @if($currentStep > 1)
                        <button type="button"
                                wire:click="previousStep"
                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Zurück
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($currentStep < 4)
                        <button type="button"
                                wire:click="nextStep"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50">
                            Weiter
                            <svg class="w-5 h-5 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @elseif($currentStep === 4)
                        <button type="button"
                                wire:click="confirmBooking"
                                wire:loading.attr="disabled"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="confirmBooking">Termin buchen</span>
                            <span wire:loading wire:target="confirmBooking">
                                <svg class="animate-spin h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Wird gebucht...
                            </span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>