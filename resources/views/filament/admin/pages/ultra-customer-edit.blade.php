<x-filament-panels::page>
    <style>
        /* Ultra Customer Edit Styles */
        .customer-edit-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        @media (max-width: 1280px) {
            .customer-edit-layout {
                grid-template-columns: 1fr;
            }
        }

        .edit-main {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark .edit-main {
            background: var(--filament-gray-800, #1f2937);
        }

        .customer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .customer-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .customer-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .sidebar-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .dark .sidebar-card {
            background: var(--filament-gray-800, #1f2937);
        }

        .lifetime-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-box {
            text-align: center;
            padding: 1rem;
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
        }

        .dark .stat-box {
            background: var(--filament-gray-900, #111827);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--filament-primary-600, #2563eb);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--filament-gray-500, #6b7280);
            margin-top: 0.25rem;
        }

        .risk-indicator {
            background: var(--filament-danger-50, #fef2f2);
            border: 1px solid var(--filament-danger-200, #fecaca);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .dark .risk-indicator {
            background: var(--filament-danger-900/20);
            border-color: var(--filament-danger-700, #b91c1c);
        }

        .loyalty-progress {
            margin-top: 1rem;
        }

        .progress-bar {
            height: 8px;
            background: var(--filament-gray-200, #e5e7eb);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .quick-action {
            padding: 0.75rem;
            text-align: center;
            background: var(--filament-gray-50, #f9fafb);
            border: 1px solid var(--filament-gray-300, #d1d5db);
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .quick-action {
            background: var(--filament-gray-900, #111827);
            border-color: var(--filament-gray-700, #374151);
        }

        .quick-action:hover {
            border-color: var(--filament-primary-500, #3b82f6);
            background: var(--filament-primary-50, #eff6ff);
        }

        .dark .quick-action:hover {
            background: var(--filament-primary-900/20);
        }

        .tag-editor {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: var(--filament-primary-100, #dbeafe);
            color: var(--filament-primary-700, #1d4ed8);
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .dark .tag {
            background: var(--filament-primary-900/30);
            color: var(--filament-primary-400, #60a5fa);
        }

        .tag-remove {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .tag-remove:hover {
            opacity: 1;
        }

        .timeline-event {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1rem;
            border-left: 2px solid var(--filament-gray-200, #e5e7eb);
        }

        .dark .timeline-event {
            border-color: var(--filament-gray-700, #374151);
        }

        .timeline-event:last-child {
            border-left: none;
        }

        .timeline-dot {
            position: absolute;
            left: -5px;
            top: 4px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--filament-primary-500, #3b82f6);
            border: 2px solid white;
        }

        .timeline-event-content {
            font-size: 0.875rem;
        }

        .timeline-event-time {
            font-size: 0.75rem;
            color: var(--filament-gray-500, #6b7280);
        }
    </style>

    <div class="customer-edit-layout">
        <!-- Main Content -->
        <div class="edit-main">
            <!-- Customer Header -->
            <div class="customer-header">
                <div class="header-content">
                    <div class="customer-avatar">
                        {{ substr($record->name, 0, 2) }}
                    </div>
                    <h2 class="text-2xl font-bold">{{ $record->name }}</h2>
                    <p class="opacity-90">Customer since {{ $record->created_at->format('F Y') }}</p>
                    <div class="flex gap-2 mt-3">
                        @if($record->is_vip)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-400/20 text-yellow-100">
                                ⭐ VIP Customer
                            </span>
                        @endif
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 text-white">
                            {{ ucfirst($record->customer_type) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                            {{ $record->status === 'active' ? 'bg-green-400/20 text-green-100' : 
                               ($record->status === 'blocked' ? 'bg-red-400/20 text-red-100' : 
                                'bg-gray-400/20 text-gray-100') }}">
                            {{ ucfirst($record->status) }}
                        </span>
                    </div>
                </div>
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
            <!-- Lifetime Stats -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="w-5 h-5 text-primary-500" />
                    Lifetime Statistics
                </h3>
                <div class="lifetime-stats">
                    <div class="stat-box">
                        <div class="stat-value">{{ $record->appointment_count }}</div>
                        <div class="stat-label">Total Visits</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">€{{ number_format($record->total_spent ?? 0, 0) }}</div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">{{ $record->no_show_count }}</div>
                        <div class="stat-label">No Shows</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">
                            @php
                                $rate = $record->appointment_count > 0 
                                    ? round(($record->no_show_count / $record->appointment_count) * 100) 
                                    : 0;
                            @endphp
                            {{ $rate }}%
                        </div>
                        <div class="stat-label">No Show Rate</div>
                    </div>
                </div>

                @if($rate > 20)
                <div class="risk-indicator">
                    <p class="font-medium text-danger-800 dark:text-danger-300 flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        High Risk Customer
                    </p>
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-1">
                        This customer has a high no-show rate. Consider requiring deposits.
                    </p>
                </div>
                @endif
            </div>

            <!-- Loyalty Progress -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-heart class="w-5 h-5 text-primary-500" />
                    Loyalty Status
                </h3>
                
                @php
                    $nextLevel = $record->appointment_count < 5 ? 5 : 
                               ($record->appointment_count < 10 ? 10 : 
                               ($record->appointment_count < 25 ? 25 : 50));
                    $progress = min(($record->appointment_count / $nextLevel) * 100, 100);
                @endphp
                
                <div class="loyalty-progress">
                    <div class="flex justify-between text-sm mb-2">
                        <span>{{ $record->appointment_count }} visits</span>
                        <span>Next: {{ $nextLevel }} visits</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ $nextLevel - $record->appointment_count }} more visits to reach the next level
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3">Quick Actions</h3>
                <div class="quick-actions">
                    <button class="quick-action" onclick="bookAppointment()">
                        <x-heroicon-o-calendar class="w-5 h-5 mx-auto mb-1" />
                        Book Appointment
                    </button>
                    <button class="quick-action" onclick="sendMessage()">
                        <x-heroicon-o-chat-bubble-left class="w-5 h-5 mx-auto mb-1" />
                        Send Message
                    </button>
                    <button class="quick-action" onclick="viewHistory()">
                        <x-heroicon-o-clock class="w-5 h-5 mx-auto mb-1" />
                        View History
                    </button>
                    <button class="quick-action" onclick="exportData()">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 mx-auto mb-1" />
                        Export Data
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-primary-500" />
                    Recent Activity
                </h3>
                <div class="space-y-2">
                    @php
                        $activities = [
                            ['type' => 'appointment', 'text' => 'Appointment completed', 'time' => '2 days ago'],
                            ['type' => 'call', 'text' => 'Phone call received', 'time' => '1 week ago'],
                            ['type' => 'update', 'text' => 'Profile updated', 'time' => '2 weeks ago'],
                        ];
                    @endphp
                    
                    @foreach($activities as $activity)
                    <div class="timeline-event">
                        <div class="timeline-dot"></div>
                        <div class="timeline-event-content">
                            <p class="font-medium">{{ $activity['text'] }}</p>
                            <p class="timeline-event-time">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Tags Management -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-tag class="w-5 h-5 text-primary-500" />
                    Customer Tags
                </h3>
                <div class="tag-editor">
                    @foreach($record->tags ?? [] as $tag)
                    <span class="tag">
                        {{ $tag }}
                        <x-heroicon-o-x-mark class="w-3 h-3 tag-remove" onclick="removeTag('{{ $tag }}')" />
                    </span>
                    @endforeach
                    <button type="button" class="tag" style="background: transparent; border: 1px dashed" onclick="addTag()">
                        <x-heroicon-o-plus class="w-3 h-3" />
                        Add tag
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bookAppointment() {
            window.location.href = '{{ route('filament.admin.resources.ultimate-appointments.create') }}?customer_id={{ $record->id }}';
        }

        function sendMessage() {
            // Open message modal
            alert('Message modal coming soon!');
        }

        function viewHistory() {
            window.location.href = '{{ route('filament.admin.resources.ultimate-customers.view', $record) }}#history';
        }

        function exportData() {
            if (confirm('Export all customer data?')) {
                // Trigger export
                window.location.href = '{{ route('filament.admin.resources.ultimate-customers.export', $record) }}';
            }
        }

        function removeTag(tag) {
            // Remove tag logic
            alert('Removing tag: ' + tag);
        }

        function addTag() {
            const tag = prompt('Enter new tag:');
            if (tag) {
                // Add tag logic
                alert('Adding tag: ' + tag);
            }
        }

        // Auto-save indicator
        let saveTimer;
        document.addEventListener('input', function(e) {
            if (e.target.form) {
                clearTimeout(saveTimer);
                document.querySelector('.form-helper')?.classList.add('saving');
                
                saveTimer = setTimeout(() => {
                    document.querySelector('.form-helper')?.classList.remove('saving');
                }, 1000);
            }
        });
    </script>
</x-filament-panels::page>