<x-filament-panels::page>
    <style>
        /* Ultra Appointment View Styles */
        .appointment-view-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .appointment-view-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .appointment-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .view-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 1280px) {
            .view-grid {
                grid-template-columns: 1fr;
            }
        }

        .view-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .dark .view-section {
            background: var(--filament-gray-800, #1f2937);
        }

        .view-section:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .calendar-preview {
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .dark .calendar-preview {
            background: var(--filament-gray-900, #111827);
        }

        .calendar-day {
            font-size: 3rem;
            font-weight: bold;
            color: var(--filament-primary-600, #2563eb);
            line-height: 1;
        }

        .calendar-month {
            font-size: 1.25rem;
            color: var(--filament-gray-600, #4b5563);
            margin-bottom: 0.5rem;
        }

        .calendar-time {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 1rem;
        }

        .service-details {
            display: flex;
            items-center;
            gap: 1.5rem;
            background: var(--filament-primary-50, #eff6ff);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .dark .service-details {
            background: var(--filament-primary-900/20);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            background: var(--filament-primary-500, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .timeline-visual {
            position: relative;
            padding: 2rem 0;
        }

        .timeline-bar {
            height: 8px;
            background: var(--filament-gray-200, #e5e7eb);
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }

        .timeline-progress {
            height: 100%;
            background: var(--filament-success-500, #10b981);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .timeline-markers {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .timeline-marker {
            text-align: center;
            flex: 1;
        }

        .marker-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--filament-gray-300, #d1d5db);
            margin: 0 auto 0.5rem;
        }

        .marker-dot.active {
            background: var(--filament-success-500, #10b981);
        }

        .customer-card {
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            items-center;
            gap: 1rem;
        }

        .dark .customer-card {
            background: var(--filament-gray-900, #111827);
        }

        .customer-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--filament-primary-500, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 150px;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            height: 200px;
            margin-top: 1rem;
            background: var(--filament-gray-100, #f3f4f6);
        }

        .reminder-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--filament-info-50, #eff6ff);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .dark .reminder-status {
            background: var(--filament-info-900/20);
        }

        @media print {
            .appointment-view-hero {
                background: none;
                color: black;
                border: 2px solid #000;
            }

            .action-buttons {
                display: none;
            }
        }
    </style>

    <!-- Hero Section -->
    <div class="appointment-view-hero">
        <div class="hero-content">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold mb-2">
                        {{ $record->service->name ?? 'Appointment' }}
                    </h1>
                    <p class="text-xl opacity-90">
                        {{ $record->customer->name }}
                    </p>
                </div>
                <div class="appointment-status-badge">
                    @if($record->status === 'confirmed')
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                    @elseif($record->status === 'completed')
                        <x-heroicon-o-check-badge class="w-5 h-5" />
                    @elseif($record->status === 'cancelled')
                        <x-heroicon-o-x-circle class="w-5 h-5" />
                    @else
                        <x-heroicon-o-clock class="w-5 h-5" />
                    @endif
                    {{ ucfirst($record->status) }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div>
                    <p class="opacity-80 text-sm">Date & Time</p>
                    <p class="text-lg font-semibold">{{ $record->starts_at->format('F j, Y - g:i A') }}</p>
                </div>
                <div>
                    <p class="opacity-80 text-sm">Duration</p>
                    <p class="text-lg font-semibold">{{ $record->starts_at->diffInMinutes($record->ends_at) }} minutes</p>
                </div>
                <div>
                    <p class="opacity-80 text-sm">Price</p>
                    <p class="text-lg font-semibold">€{{ number_format($record->price ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="view-grid">
        <!-- Main Content -->
        <div class="space-y-6">
            <!-- Service Details -->
            <div class="view-section">
                <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-sparkles class="w-6 h-6 text-primary-500" />
                    Service Details
                </h2>
                <div class="service-details">
                    <div class="service-icon">
                        <x-heroicon-o-sparkles class="w-8 h-8" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg">{{ $record->service->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ $record->service->description ?? 'Standard service appointment' }}
                        </p>
                        <div class="flex gap-4 mt-2 text-sm">
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-clock class="w-4 h-4" />
                                {{ $record->service->duration ?? 60 }} min
                            </span>
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-currency-euro class="w-4 h-4" />
                                {{ number_format($record->service->price ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Progress -->
            <div class="view-section">
                <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="w-6 h-6 text-primary-500" />
                    Appointment Progress
                </h2>
                <div class="timeline-visual">
                    <div class="timeline-bar">
                        <div class="timeline-progress" style="width: {{ $record->status === 'completed' ? '100' : ($record->status === 'checked_in' ? '75' : ($record->status === 'confirmed' ? '50' : '25')) }}%"></div>
                    </div>
                    <div class="timeline-markers">
                        <div class="timeline-marker">
                            <div class="marker-dot active"></div>
                            <p class="text-sm">Scheduled</p>
                        </div>
                        <div class="timeline-marker">
                            <div class="marker-dot {{ in_array($record->status, ['confirmed', 'checked_in', 'completed']) ? 'active' : '' }}"></div>
                            <p class="text-sm">Confirmed</p>
                        </div>
                        <div class="timeline-marker">
                            <div class="marker-dot {{ in_array($record->status, ['checked_in', 'completed']) ? 'active' : '' }}"></div>
                            <p class="text-sm">Checked In</p>
                        </div>
                        <div class="timeline-marker">
                            <div class="marker-dot {{ $record->status === 'completed' ? 'active' : '' }}"></div>
                            <p class="text-sm">Completed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes & Instructions -->
            @if($record->notes || $record->customer_notes)
            <div class="view-section">
                <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-500" />
                    Notes & Instructions
                </h2>
                
                @if($record->notes)
                <div class="mb-4">
                    <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Internal Notes</h3>
                    <div class="prose dark:prose-invert max-w-none">
                        {!! nl2br(e($record->notes)) !!}
                    </div>
                </div>
                @endif

                @if($record->customer_notes)
                <div>
                    <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Customer Notes</h3>
                    <div class="prose dark:prose-invert max-w-none">
                        {!! nl2br(e($record->customer_notes)) !!}
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="view-section">
                <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                <div class="action-buttons">
                    @if($record->status === 'confirmed' && $record->starts_at->isToday())
                    <button class="action-btn bg-success-500 text-white hover:bg-success-600">
                        <x-heroicon-o-check-badge class="w-5 h-5 inline mr-2" />
                        Check In
                    </button>
                    @endif
                    
                    <button class="action-btn bg-primary-500 text-white hover:bg-primary-600">
                        <x-heroicon-o-pencil class="w-5 h-5 inline mr-2" />
                        Edit
                    </button>
                    
                    <button class="action-btn bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600">
                        <x-heroicon-o-printer class="w-5 h-5 inline mr-2" />
                        Print
                    </button>
                    
                    <button class="action-btn border-2 border-gray-300 dark:border-gray-600 hover:border-primary-500">
                        <x-heroicon-o-share class="w-5 h-5 inline mr-2" />
                        Share
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Calendar Preview -->
            <div class="view-section">
                <div class="calendar-preview">
                    <div class="calendar-month">{{ $record->starts_at->format('F') }}</div>
                    <div class="calendar-day">{{ $record->starts_at->format('j') }}</div>
                    <div class="calendar-time">{{ $record->starts_at->format('g:i A') }}</div>
                    <div class="text-sm text-gray-500 mt-2">{{ $record->starts_at->format('l') }}</div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="view-section">
                <h3 class="font-semibold text-lg mb-3">Customer Information</h3>
                <div class="customer-card">
                    <div class="customer-avatar">
                        {{ substr($record->customer->name, 0, 2) }}
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ $record->customer->name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->customer->phone }}</p>
                        @if($record->customer->email)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->customer->email }}</p>
                        @endif
                    </div>
                </div>
                
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Appointments</span>
                        <span class="font-medium">{{ $record->customer->appointments()->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Customer Since</span>
                        <span class="font-medium">{{ $record->customer->created_at->format('M Y') }}</span>
                    </div>
                    @if($record->customer->is_vip)
                    <div class="mt-2 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 rounded-lg text-center">
                        ⭐ VIP Customer
                    </div>
                    @endif
                </div>
            </div>

            <!-- Staff & Location -->
            <div class="view-section">
                <h3 class="font-semibold text-lg mb-3">Staff & Location</h3>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Staff Member</p>
                        <p class="font-medium">{{ $record->staff->name ?? 'Unassigned' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Branch</p>
                        <p class="font-medium">{{ $record->branch->name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->branch->address }}</p>
                    </div>
                </div>

                <!-- Map Preview (Placeholder) -->
                <div class="map-container">
                    <!-- Map would go here -->
                    <div class="w-full h-full flex items-center justify-center text-gray-500">
                        <x-heroicon-o-map-pin class="w-8 h-8" />
                    </div>
                </div>
            </div>

            <!-- Reminders & Notifications -->
            <div class="view-section">
                <h3 class="font-semibold text-lg mb-3">Notifications</h3>
                
                <div class="space-y-2">
                    @if($record->reminder_sent)
                    <div class="reminder-status">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                        <span>Reminder sent</span>
                    </div>
                    @else
                    <div class="reminder-status">
                        <x-heroicon-o-clock class="w-5 h-5 text-info-500" />
                        <span>Reminder pending</span>
                    </div>
                    @endif
                    
                    @if($record->confirmation_sent)
                    <div class="reminder-status">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                        <span>Confirmation sent</span>
                    </div>
                    @endif
                </div>

                <button class="mt-3 w-full px-4 py-2 bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-900/30 transition text-sm font-medium">
                    Send Manual Reminder
                </button>
            </div>
        </div>
    </div>

    {{ $this->infolist }}

    <script>
        // Print functionality
        document.querySelector('[onclick*="print"]')?.addEventListener('click', function() {
            window.print();
        });

        // Share functionality
        document.querySelector('[onclick*="share"]')?.addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: 'Appointment Details',
                    text: `Appointment with {{ $record->customer->name }} on {{ $record->starts_at->format('M j, Y') }}`,
                    url: window.location.href
                });
            } else {
                // Fallback to copying URL
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        });
    </script>
</x-filament-panels::page>