<x-filament-panels::page>
    <style>
        /* Ultra Appointment Create Styles */
        .appointment-wizard {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }

        .dark .appointment-wizard {
            background: var(--filament-gray-800, #1f2937);
        }

        .wizard-progress {
            background: var(--filament-gray-50, #f9fafb);
            padding: 2rem;
            border-bottom: 1px solid var(--filament-gray-200, #e5e7eb);
        }

        .dark .wizard-progress {
            background: var(--filament-gray-900, #111827);
            border-color: var(--filament-gray-700, #374151);
        }

        .smart-availability {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .time-slot {
            padding: 0.75rem;
            text-align: center;
            border: 2px solid var(--filament-gray-300, #d1d5db);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .dark .time-slot {
            border-color: var(--filament-gray-600, #4b5563);
        }

        .time-slot:hover {
            border-color: var(--filament-primary-500, #3b82f6);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .time-slot.selected {
            background: var(--filament-primary-500, #3b82f6);
            color: white;
            border-color: var(--filament-primary-600, #2563eb);
        }

        .time-slot.unavailable {
            background: var(--filament-gray-100, #f3f4f6);
            color: var(--filament-gray-400, #9ca3af);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .dark .time-slot.unavailable {
            background: var(--filament-gray-700, #374151);
        }

        .time-slot-time {
            font-weight: 600;
            font-size: 1rem;
        }

        .time-slot-label {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }

        .ai-suggestions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .suggestion-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .suggestion-badge:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .customer-preview {
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            items-center;
            gap: 1rem;
        }

        .dark .customer-preview {
            background: var(--filament-gray-900, #111827);
        }

        .customer-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--filament-primary-500, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
        }

        .service-card {
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--filament-gray-50, #f9fafb);
        }

        .dark .service-card {
            background: var(--filament-gray-900, #111827);
        }

        .service-card:hover {
            border-color: var(--filament-primary-500, #3b82f6);
            transform: translateY(-2px);
        }

        .service-card.selected {
            border-color: var(--filament-primary-500, #3b82f6);
            background: var(--filament-primary-50, #eff6ff);
        }

        .dark .service-card.selected {
            background: var(--filament-primary-900/20);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .wizard-step-content {
            animation: slideIn 0.3s ease-out;
        }
    </style>

    <div class="appointment-wizard">
        <!-- AI Suggestions -->
        <div class="ai-suggestions" x-data="{ show: true }" x-show="show" x-collapse>
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-5 h-5" />
                        AI-Powered Scheduling Assistant
                    </h3>
                    <p class="text-sm opacity-90 mt-1">
                        Based on customer history and availability patterns
                    </p>
                </div>
                <button @click="show = false" class="text-white/80 hover:text-white">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="suggestions-list">
                <span class="suggestion-badge" onclick="selectOptimalTime()">
                    <x-heroicon-o-clock class="w-4 h-4 inline mr-1" />
                    Best Time: Tomorrow 10:00 AM
                </span>
                <span class="suggestion-badge" onclick="selectPreferredStaff()">
                    <x-heroicon-o-user class="w-4 h-4 inline mr-1" />
                    Preferred: Sarah Miller
                </span>
                <span class="suggestion-badge" onclick="selectRecommendedService()">
                    <x-heroicon-o-sparkles class="w-4 h-4 inline mr-1" />
                    Recommended: Premium Consultation
                </span>
                <span class="suggestion-badge" onclick="enableRecurring()">
                    <x-heroicon-o-arrow-path class="w-4 h-4 inline mr-1" />
                    Make it recurring (4 weeks)
                </span>
            </div>
        </div>

        <!-- Form Content -->
        <div class="p-6">
            <x-filament-panels::form wire:submit="create">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>

        <!-- Quick Time Selection (Outside wizard for easy access) -->
        <div class="p-6 border-t dark:border-gray-700" x-data="timeSlotPicker()">
            <h4 class="font-semibold mb-3 flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-primary-500" />
                Quick Time Selection
            </h4>
            <div class="smart-availability">
                @php
                    $times = [
                        '09:00' => 'Morning',
                        '10:00' => 'Morning',
                        '11:00' => 'Morning',
                        '14:00' => 'Afternoon',
                        '15:00' => 'Afternoon',
                        '16:00' => 'Afternoon',
                        '17:00' => 'Evening',
                    ];
                    $availability = ['09:00', '10:00', '14:00', '16:00']; // Mock available slots
                @endphp

                @foreach($times as $time => $period)
                    <div class="time-slot {{ in_array($time, $availability) ? '' : 'unavailable' }}"
                         @if(in_array($time, $availability))
                         @click="selectTime('{{ $time }}')"
                         :class="{ 'selected': selectedTime === '{{ $time }}' }"
                         @endif>
                        <div class="time-slot-time">{{ $time }}</div>
                        <div class="time-slot-label">{{ $period }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function timeSlotPicker() {
            return {
                selectedTime: null,
                selectTime(time) {
                    this.selectedTime = time;
                    // Update the form field
                    const dateField = document.querySelector('input[name="starts_at"]');
                    if (dateField) {
                        const currentDate = dateField.value ? dateField.value.split(' ')[0] : new Date().toISOString().split('T')[0];
                        dateField.value = `${currentDate} ${time}`;
                        dateField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
        }

        function selectOptimalTime() {
            // Auto-fill with optimal time
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(10, 0, 0, 0);
            
            const dateField = document.querySelector('input[name="starts_at"]');
            if (dateField) {
                // Format: YYYY-MM-DD HH:mm
                const formatted = tomorrow.toISOString().slice(0, 16).replace('T', ' ');
                dateField.value = formatted;
                dateField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        function selectPreferredStaff() {
            // Select preferred staff member
            const staffSelect = document.querySelector('select[name="staff_id"]');
            if (staffSelect) {
                // Find Sarah Miller option (or first available)
                const options = staffSelect.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].text.includes('Sarah') || i === 1) {
                        staffSelect.value = options[i].value;
                        staffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }

        function selectRecommendedService() {
            // Select recommended service
            const serviceSelect = document.querySelector('select[name="service_id"]');
            if (serviceSelect && serviceSelect.options.length > 1) {
                serviceSelect.value = serviceSelect.options[1].value;
                serviceSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function enableRecurring() {
            // Enable recurring appointment
            const recurringToggle = document.querySelector('input[name="is_recurring"]');
            if (recurringToggle) {
                recurringToggle.checked = true;
                recurringToggle.dispatchEvent(new Event('change', { bubbles: true }));
                
                // Set pattern to weekly
                setTimeout(() => {
                    const patternSelect = document.querySelector('select[name="recurrence_pattern"]');
                    if (patternSelect) {
                        patternSelect.value = 'weekly';
                        patternSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    
                    // Set count to 4
                    const countInput = document.querySelector('input[name="recurrence_count"]');
                    if (countInput) {
                        countInput.value = '4';
                        countInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }, 100);
            }
        }
    </script>
</x-filament-panels::page>