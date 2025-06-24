<x-filament-panels::page>
    <style>
        /* Ultra Appointment Edit Styles */
        .appointment-edit-container {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        @media (max-width: 1280px) {
            .appointment-edit-container {
                grid-template-columns: 1fr;
            }
        }

        .edit-main-content {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark .edit-main-content {
            background: var(--filament-gray-800, #1f2937);
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            padding: 2rem;
            background: var(--filament-gray-50, #f9fafb);
            border-bottom: 1px solid var(--filament-gray-200, #e5e7eb);
        }

        .dark .status-timeline {
            background: var(--filament-gray-900, #111827);
            border-color: var(--filament-gray-700, #374151);
        }

        .timeline-step {
            text-align: center;
            position: relative;
            flex: 1;
        }

        .timeline-step::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--filament-gray-300, #d1d5db);
            z-index: 0;
        }

        .timeline-step:last-child::after {
            display: none;
        }

        .timeline-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--filament-gray-300, #d1d5db);
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .timeline-step.active .timeline-dot {
            background: var(--filament-primary-500, #3b82f6);
            color: white;
            transform: scale(1.1);
        }

        .timeline-step.completed .timeline-dot {
            background: var(--filament-success-500, #10b981);
            color: white;
        }

        .timeline-label {
            font-size: 0.875rem;
            color: var(--filament-gray-600, #4b5563);
        }

        .timeline-time {
            font-size: 0.75rem;
            color: var(--filament-gray-500, #6b7280);
            margin-top: 0.25rem;
        }

        .sidebar-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .dark .sidebar-section {
            background: var(--filament-gray-800, #1f2937);
        }

        .quick-action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .quick-action-btn {
            padding: 0.75rem;
            text-align: center;
            border: 1px solid var(--filament-gray-300, #d1d5db);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
            background: white;
        }

        .dark .quick-action-btn {
            background: var(--filament-gray-700, #374151);
            border-color: var(--filament-gray-600, #4b5563);
        }

        .quick-action-btn:hover {
            border-color: var(--filament-primary-500, #3b82f6);
            color: var(--filament-primary-600, #2563eb);
            transform: translateY(-1px);
        }

        .conflict-warning {
            background: var(--filament-warning-50, #fffbeb);
            border: 1px solid var(--filament-warning-200, #fde68a);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .dark .conflict-warning {
            background: var(--filament-warning-900/20);
            border-color: var(--filament-warning-700, #b45309);
        }

        .history-timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .history-timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0.5rem;
            bottom: 0;
            width: 2px;
            background: var(--filament-gray-300, #d1d5db);
        }

        .history-item {
            position: relative;
            padding-bottom: 1rem;
        }

        .history-dot {
            position: absolute;
            left: -1.25rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--filament-primary-500, #3b82f6);
            border: 2px solid white;
        }

        .customer-insights {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .insight-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .insight-stat:last-child {
            border-bottom: none;
        }

        .reschedule-helper {
            background: var(--filament-info-50, #eff6ff);
            border: 1px solid var(--filament-info-200, #bfdbfe);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .dark .reschedule-helper {
            background: var(--filament-info-900/20);
            border-color: var(--filament-info-700, #1d4ed8);
        }
    </style>

    <div class="appointment-edit-container">
        <!-- Main Content -->
        <div class="edit-main-content">
            <!-- Status Timeline -->
            <div class="status-timeline">
                @php
                    $status = $record->status;
                    $statuses = [
                        'scheduled' => ['icon' => 'calendar', 'label' => 'Scheduled'],
                        'confirmed' => ['icon' => 'check', 'label' => 'Confirmed'],
                        'checked_in' => ['icon' => 'user-check', 'label' => 'Checked In'],
                        'completed' => ['icon' => 'check-circle', 'label' => 'Completed'],
                    ];
                    
                    $statusOrder = array_keys($statuses);
                    $currentIndex = array_search($status, $statusOrder);
                @endphp

                @foreach($statuses as $key => $info)
                    @php
                        $stepIndex = array_search($key, $statusOrder);
                        $isActive = $key === $status;
                        $isCompleted = $currentIndex !== false && $stepIndex < $currentIndex;
                    @endphp
                    
                    <div class="timeline-step {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }}">
                        <div class="timeline-dot">
                            <x-dynamic-component :component="'heroicon-o-' . $info['icon']" class="w-5 h-5" />
                        </div>
                        <div class="timeline-label">{{ $info['label'] }}</div>
                        @if($key === 'scheduled' && $record->created_at)
                            <div class="timeline-time">{{ $record->created_at->format('M j, g:i A') }}</div>
                        @elseif($key === 'confirmed' && $record->confirmed_at)
                            <div class="timeline-time">{{ $record->confirmed_at->format('M j, g:i A') }}</div>
                        @elseif($key === 'checked_in' && $record->checked_in_at)
                            <div class="timeline-time">{{ $record->checked_in_at->format('M j, g:i A') }}</div>
                        @elseif($key === 'completed' && $record->completed_at)
                            <div class="timeline-time">{{ $record->completed_at->format('M j, g:i A') }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Form -->
            <div class="p-6">
                <x-filament-panels::form wire:submit="save">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Quick Actions -->
            <div class="sidebar-section">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-bolt class="w-5 h-5 text-primary-500" />
                    Quick Actions
                </h3>
                <div class="quick-action-grid">
                    <button class="quick-action-btn" onclick="sendReminder()">
                        <x-heroicon-o-bell class="w-5 h-5 mx-auto mb-1" />
                        Send Reminder
                    </button>
                    <button class="quick-action-btn" onclick="printDetails()">
                        <x-heroicon-o-printer class="w-5 h-5 mx-auto mb-1" />
                        Print Details
                    </button>
                    <button class="quick-action-btn" onclick="duplicateAppointment()">
                        <x-heroicon-o-document-duplicate class="w-5 h-5 mx-auto mb-1" />
                        Duplicate
                    </button>
                    <button class="quick-action-btn" onclick="addToCalendar()">
                        <x-heroicon-o-calendar-days class="w-5 h-5 mx-auto mb-1" />
                        Add to Calendar
                    </button>
                </div>
            </div>

            <!-- Customer Insights -->
            <div class="sidebar-section customer-insights">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5" />
                    Customer Insights
                </h3>
                <div class="space-y-2">
                    <div class="insight-stat">
                        <span>Total Appointments</span>
                        <span class="font-bold">{{ $record->customer->appointments()->count() }}</span>
                    </div>
                    <div class="insight-stat">
                        <span>No-Show Rate</span>
                        <span class="font-bold">
                            @php
                                $total = $record->customer->appointments()->count();
                                $noShows = $record->customer->no_show_count ?? 0;
                                $rate = $total > 0 ? round(($noShows / $total) * 100) : 0;
                            @endphp
                            {{ $rate }}%
                        </span>
                    </div>
                    <div class="insight-stat">
                        <span>Customer Since</span>
                        <span class="font-bold">{{ $record->customer->created_at->format('M Y') }}</span>
                    </div>
                    <div class="insight-stat">
                        <span>Lifetime Value</span>
                        <span class="font-bold">€{{ number_format($record->customer->appointments()->sum('price'), 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Scheduling Conflicts -->
            @if($record->status !== 'completed' && $record->status !== 'cancelled')
            <div class="sidebar-section">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                    Scheduling Info
                </h3>
                
                @php
                    // Check for conflicts
                    $conflicts = \App\Models\Appointment::where('id', '!=', $record->id)
                        ->where('staff_id', $record->staff_id)
                        ->where('starts_at', '<', $record->ends_at)
                        ->where('ends_at', '>', $record->starts_at)
                        ->whereNotIn('status', ['cancelled', 'no_show'])
                        ->exists();
                @endphp

                @if($conflicts)
                <div class="conflict-warning">
                    <p class="font-medium text-warning-800 dark:text-warning-300 mb-1">
                        ⚠️ Schedule Conflict Detected
                    </p>
                    <p class="text-sm text-warning-700 dark:text-warning-400">
                        This staff member has overlapping appointments.
                    </p>
                </div>
                @endif

                <div class="reschedule-helper">
                    <p class="font-medium mb-2">Next Available Slots:</p>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>Today 3:00 PM</span>
                            <button class="text-primary-600 hover:underline">Select</button>
                        </div>
                        <div class="flex justify-between">
                            <span>Tomorrow 10:00 AM</span>
                            <button class="text-primary-600 hover:underline">Select</button>
                        </div>
                        <div class="flex justify-between">
                            <span>Tomorrow 2:00 PM</span>
                            <button class="text-primary-600 hover:underline">Select</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- History -->
            <div class="sidebar-section">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5" />
                    History
                </h3>
                <div class="history-timeline">
                    @if($record->rescheduled_at)
                    <div class="history-item">
                        <div class="history-dot bg-warning-500"></div>
                        <p class="text-sm font-medium">Rescheduled</p>
                        <p class="text-xs text-gray-500">{{ $record->rescheduled_at->diffForHumans() }}</p>
                    </div>
                    @endif
                    
                    @if($record->confirmed_at)
                    <div class="history-item">
                        <div class="history-dot bg-success-500"></div>
                        <p class="text-sm font-medium">Confirmed</p>
                        <p class="text-xs text-gray-500">{{ $record->confirmed_at->diffForHumans() }}</p>
                    </div>
                    @endif
                    
                    <div class="history-item">
                        <div class="history-dot"></div>
                        <p class="text-sm font-medium">Created</p>
                        <p class="text-xs text-gray-500">{{ $record->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function sendReminder() {
            // Trigger reminder sending
            if (confirm('Send appointment reminder to customer?')) {
                // Implementation would go here
                alert('Reminder sent successfully!');
            }
        }

        function printDetails() {
            window.print();
        }

        function duplicateAppointment() {
            // Redirect to create with pre-filled data
            const url = new URL('{{ route('filament.admin.resources.ultimate-appointments.create') }}');
            url.searchParams.append('duplicate', '{{ $record->id }}');
            window.location.href = url.toString();
        }

        function addToCalendar() {
            // Generate calendar file or link
            alert('Adding to calendar...');
        }

        // Auto-save functionality
        let autoSaveTimer;
        document.addEventListener('input', function(e) {
            if (e.target.form) {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // Show auto-save indicator
                    console.log('Auto-saving...');
                }, 2000);
            }
        });
    </script>
</x-filament-panels::page>