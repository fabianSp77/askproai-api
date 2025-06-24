<x-filament-panels::page>
    <style>
        /* Ultra Customer View Styles */
        .customer-profile-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem;
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .profile-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .profile-content {
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .profile-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .view-layout {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 1280px) {
            .view-layout {
                grid-template-columns: 1fr;
            }
        }

        .view-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark .view-card {
            background: var(--filament-gray-800, #1f2937);
        }

        .analytics-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }

        .dark .metric-card {
            background: var(--filament-gray-900, #111827);
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            font-size: 0.875rem;
            color: var(--filament-gray-500, #6b7280);
        }

        .metric-trend {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .trend-up {
            color: var(--filament-success-600, #059669);
        }

        .trend-down {
            color: var(--filament-danger-600, #dc2626);
        }

        .customer-journey {
            position: relative;
            padding: 2rem 0;
        }

        .journey-line {
            position: absolute;
            left: 1.5rem;
            top: 3rem;
            bottom: 1rem;
            width: 2px;
            background: var(--filament-gray-200, #e5e7eb);
        }

        .journey-item {
            position: relative;
            padding-left: 4rem;
            margin-bottom: 2rem;
        }

        .journey-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 3rem;
            height: 3rem;
            background: white;
            border: 2px solid var(--filament-gray-200, #e5e7eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dark .journey-icon {
            background: var(--filament-gray-800, #1f2937);
            border-color: var(--filament-gray-700, #374151);
        }

        .journey-item.highlight .journey-icon {
            background: var(--filament-primary-500, #3b82f6);
            border-color: var(--filament-primary-600, #2563eb);
            color: white;
        }

        .segment-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--filament-primary-50, #eff6ff);
            border: 1px solid var(--filament-primary-200, #bfdbfe);
            border-radius: 12px;
            font-weight: 500;
        }

        .dark .segment-indicator {
            background: var(--filament-primary-900/20);
            border-color: var(--filament-primary-700, #1d4ed8);
        }

        .appointment-timeline {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 1rem;
        }

        .appointment-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .dark .appointment-item {
            background: var(--filament-gray-900, #111827);
        }

        .appointment-item:hover {
            background: var(--filament-gray-100, #f3f4f6);
            transform: translateX(4px);
        }

        .dark .appointment-item:hover {
            background: var(--filament-gray-800, #1f2937);
        }

        .communication-preferences {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .pref-item {
            text-align: center;
            padding: 1rem;
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
        }

        .dark .pref-item {
            background: var(--filament-gray-900, #111827);
        }

        .pref-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .pref-status {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .enabled {
            color: var(--filament-success-600, #059669);
        }

        .disabled {
            color: var(--filament-danger-600, #dc2626);
        }
    </style>

    <!-- Customer Profile Hero -->
    <div class="customer-profile-hero">
        <div class="profile-pattern"></div>
        <div class="profile-content">
            <div class="flex items-start justify-between">
                <div>
                    <div class="profile-avatar">
                        {{ substr($record->name, 0, 2) }}
                    </div>
                    <h1 class="text-3xl font-bold mb-2">{{ $record->name }}</h1>
                    <p class="text-xl opacity-90 flex items-center gap-2">
                        <x-heroicon-o-phone class="w-5 h-5" />
                        {{ $record->phone }}
                    </p>
                    @if($record->email)
                    <p class="opacity-80 flex items-center gap-2 mt-1">
                        <x-heroicon-o-envelope class="w-5 h-5" />
                        {{ $record->email }}
                    </p>
                    @endif
                    
                    <div class="profile-badges">
                        <div class="profile-badge">
                            <x-heroicon-o-calendar class="w-4 h-4" />
                            Customer since {{ $record->created_at->format('M Y') }}
                        </div>
                        @if($record->is_vip)
                        <div class="profile-badge">
                            ⭐ VIP Customer
                        </div>
                        @endif
                        <div class="profile-badge">
                            {{ ucfirst($record->customer_type) }}
                        </div>
                        <div class="profile-badge {{ $record->status === 'active' ? 'bg-green-500/20' : 'bg-red-500/20' }}">
                            {{ ucfirst($record->status) }}
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="segment-indicator">
                        @php
                            $segment = $record->appointment_count > 20 ? 'Loyal' : 
                                      ($record->appointment_count > 10 ? 'Regular' : 
                                      ($record->appointment_count > 5 ? 'Returning' : 'New'));
                        @endphp
                        <x-heroicon-o-chart-pie class="w-5 h-5" />
                        {{ $segment }} Customer
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="analytics-dashboard">
        <div class="metric-card">
            <div class="metric-value text-primary-600">{{ $record->appointment_count }}</div>
            <div class="metric-label">Total Appointments</div>
            <div class="metric-trend trend-up">
                <x-heroicon-o-arrow-trending-up class="w-4 h-4" />
                +15% vs last year
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value text-success-600">€{{ number_format($record->total_spent ?? 0, 0) }}</div>
            <div class="metric-label">Lifetime Value</div>
            <div class="metric-trend trend-up">
                <x-heroicon-o-arrow-trending-up class="w-4 h-4" />
                +€250 this year
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value text-warning-600">{{ $record->no_show_count }}</div>
            <div class="metric-label">No Shows</div>
            <div class="metric-trend {{ $record->no_show_count > 2 ? 'trend-down' : 'trend-up' }}">
                @if($record->no_show_count > 2)
                    <x-heroicon-o-arrow-trending-down class="w-4 h-4" />
                    Above average
                @else
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4" />
                    Below average
                @endif
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value text-info-600">
                @php
                    $avgSpent = $record->appointment_count > 0 
                        ? $record->total_spent / $record->appointment_count 
                        : 0;
                @endphp
                €{{ number_format($avgSpent, 0) }}
            </div>
            <div class="metric-label">Avg. per Visit</div>
            <div class="metric-trend trend-up">
                <x-heroicon-o-arrow-trending-up class="w-4 h-4" />
                Industry avg: €65
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value text-purple-600">
                @php
                    $lastVisit = $record->appointments()->latest('starts_at')->first();
                    $daysSince = $lastVisit ? $lastVisit->starts_at->diffInDays(now()) : 999;
                @endphp
                {{ $daysSince }}d
            </div>
            <div class="metric-label">Since Last Visit</div>
            <div class="metric-trend {{ $daysSince > 90 ? 'trend-down' : 'trend-up' }}">
                @if($daysSince > 90)
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                    At risk
                @else
                    <x-heroicon-o-check-circle class="w-4 h-4" />
                    Active
                @endif
            </div>
        </div>
    </div>

    <div class="view-layout">
        <!-- Customer Journey -->
        <div class="view-card">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <x-heroicon-o-map class="w-6 h-6 text-primary-500" />
                Customer Journey
            </h2>
            
            <div class="customer-journey">
                <div class="journey-line"></div>
                
                <div class="journey-item highlight">
                    <div class="journey-icon">
                        <x-heroicon-o-user-plus class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="font-semibold">First Contact</p>
                        <p class="text-sm text-gray-500">{{ $record->created_at->format('F j, Y') }}</p>
                        <p class="text-sm">Joined via {{ $record->referral_source ?? 'Direct' }}</p>
                    </div>
                </div>
                
                @if($record->appointments()->count() > 0)
                <div class="journey-item">
                    <div class="journey-icon">
                        <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" />
                    </div>
                    <div>
                        <p class="font-semibold">First Appointment</p>
                        <p class="text-sm text-gray-500">
                            {{ $record->appointments()->oldest('starts_at')->first()->starts_at->format('F j, Y') }}
                        </p>
                    </div>
                </div>
                @endif
                
                @if($record->is_vip)
                <div class="journey-item highlight">
                    <div class="journey-icon">
                        <x-heroicon-o-star class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="font-semibold">VIP Status Achieved</p>
                        <p class="text-sm text-gray-500">After {{ $record->appointment_count }} appointments</p>
                    </div>
                </div>
                @endif
                
                <div class="journey-item">
                    <div class="journey-icon">
                        <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-success-500" />
                    </div>
                    <div>
                        <p class="font-semibold">Current Status</p>
                        <p class="text-sm text-gray-500">{{ ucfirst($segment) }} Customer</p>
                        <p class="text-sm">Next milestone: {{ $record->appointment_count < 50 ? '50 appointments' : '100 appointments' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment History -->
        <div class="view-card">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <x-heroicon-o-calendar-days class="w-6 h-6 text-primary-500" />
                Recent Appointments
            </h2>
            
            <div class="appointment-timeline">
                @forelse($record->appointments()->latest('starts_at')->limit(10)->get() as $appointment)
                <a href="{{ route('filament.admin.resources.ultimate-appointments.view', $appointment) }}" 
                   class="appointment-item">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center
                            {{ $appointment->status === 'completed' ? 'bg-success-100 text-success-600' : 
                               ($appointment->status === 'cancelled' ? 'bg-danger-100 text-danger-600' : 
                                'bg-primary-100 text-primary-600') }}">
                            <x-heroicon-o-calendar class="w-6 h-6" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">{{ $appointment->service->name ?? 'Service' }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $appointment->starts_at->format('M j, Y - g:i A') }}
                        </p>
                        <p class="text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $appointment->status === 'completed' ? 'bg-success-100 text-success-800' : 
                                   ($appointment->status === 'cancelled' ? 'bg-danger-100 text-danger-800' : 
                                    'bg-primary-100 text-primary-800') }}">
                                {{ ucfirst($appointment->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold">€{{ number_format($appointment->price ?? 0, 2) }}</p>
                    </div>
                </a>
                @empty
                <p class="text-center text-gray-500 py-8">No appointments yet</p>
                @endforelse
            </div>
            
            @if($record->appointments()->count() > 10)
            <div class="mt-4 text-center">
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                    View all {{ $record->appointments()->count() }} appointments →
                </a>
            </div>
            @endif
        </div>

        <!-- Communication & Preferences -->
        <div class="view-card">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-primary-500" />
                Communication
            </h2>
            
            <div class="mb-6">
                <h3 class="font-medium mb-3">Preferences</h3>
                <div class="communication-preferences">
                    <div class="pref-item">
                        <div class="pref-icon">📧</div>
                        <p class="font-medium">Email</p>
                        <p class="pref-status {{ $record->marketing_consent ? 'enabled' : 'disabled' }}">
                            {{ $record->marketing_consent ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                    <div class="pref-item">
                        <div class="pref-icon">💬</div>
                        <p class="font-medium">SMS</p>
                        <p class="pref-status enabled">Enabled</p>
                    </div>
                    <div class="pref-item">
                        <div class="pref-icon">📱</div>
                        <p class="font-medium">WhatsApp</p>
                        <p class="pref-status disabled">Disabled</p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="font-medium mb-3">Language</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Preferred: {{ $record->preferred_language ? strtoupper($record->preferred_language) : 'Not set' }}
                </p>
            </div>

            @if($record->notes)
            <div>
                <h3 class="font-medium mb-3">Internal Notes</h3>
                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <p class="text-sm">{{ $record->notes }}</p>
                </div>
            </div>
            @endif

            <div class="mt-6 space-y-2">
                <button class="w-full px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    <x-heroicon-o-chat-bubble-left class="w-4 h-4 inline mr-2" />
                    Send Message
                </button>
                <button class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <x-heroicon-o-calendar class="w-4 h-4 inline mr-2" />
                    Book Appointment
                </button>
            </div>
        </div>
    </div>

    {{ $this->infolist }}

    <script>
        // Quick action handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Add any interactive functionality here
        });
    </script>
</x-filament-panels::page>