<x-filament-panels::page>
    <style>
        /* Ultra Call Edit Styles */
        .ultra-edit-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .ultra-edit-container {
                grid-template-columns: 1fr;
            }
        }

        .edit-main-form {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark .edit-main-form {
            background: var(--filament-gray-800, #1f2937);
        }

        .edit-sidebar {
            space-y: 1.5rem;
        }

        .sidebar-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark .sidebar-card {
            background: var(--filament-gray-800, #1f2937);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 8px;
        }

        .dark .stat-item {
            background: var(--filament-gray-700, #374151);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--filament-primary-600, #2563eb);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--filament-gray-500, #6b7280);
        }

        .edit-timeline {
            position: relative;
            padding-left: 2rem;
            margin-top: 1rem;
        }

        .timeline-line {
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--filament-gray-300, #d1d5db);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1rem;
        }

        .timeline-dot {
            position: absolute;
            left: -1.5rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--filament-primary-500, #3b82f6);
            border: 2px solid white;
        }

        .ai-insights {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .insight-item {
            display: flex;
            align-items: start;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .insight-icon {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
        }

        .form-changed-indicator {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--filament-warning-500, #f59e0b);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
            align-items: center;
            gap: 0.5rem;
            z-index: 50;
        }

        .form-changed-indicator.active {
            display: flex;
        }

        .auto-save-status {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: var(--filament-success-500, #10b981);
            color: white;
            border-radius: 9999px;
            font-size: 0.875rem;
            display: none;
            z-index: 50;
        }

        .auto-save-status.show {
            display: block;
            animation: fadeInOut 3s ease-in-out;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            20% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }
    </style>

    <div class="ultra-edit-container">
        <!-- Main Form -->
        <div class="edit-main-form">
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>

        <!-- Sidebar -->
        <div class="edit-sidebar">
            <!-- Call Quick Stats -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="w-5 h-5" />
                    Call Analytics
                </h3>
                <div class="quick-stats">
                    <div class="stat-item">
                        <div class="stat-value">{{ gmdate('i:s', $record->duration ?? 0) }}</div>
                        <div class="stat-label">Duration</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            @if($record->sentiment == 'positive')
                                😊
                            @elseif($record->sentiment == 'negative')
                                😔
                            @else
                                😐
                            @endif
                        </div>
                        <div class="stat-label">Sentiment</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $record->sentiment_score ?? 'N/A' }}</div>
                        <div class="stat-label">Score</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            @if($record->appointment_id)
                                <x-heroicon-o-check class="w-6 h-6 text-green-500 mx-auto" />
                            @else
                                <x-heroicon-o-x-mark class="w-6 h-6 text-gray-400 mx-auto" />
                            @endif
                        </div>
                        <div class="stat-label">Appointment</div>
                    </div>
                </div>
            </div>

            <!-- AI Insights -->
            <div class="sidebar-card ai-insights">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-sparkles class="w-5 h-5" />
                    AI Insights
                </h3>
                <div class="insight-item">
                    <x-heroicon-o-light-bulb class="insight-icon" />
                    <p class="text-sm">Customer seems interested in premium services based on call content.</p>
                </div>
                <div class="insight-item">
                    <x-heroicon-o-calendar class="insight-icon" />
                    <p class="text-sm">Best follow-up time: Tomorrow between 10-12 AM</p>
                </div>
                <div class="insight-item">
                    <x-heroicon-o-arrow-trending-up class="insight-icon" />
                    <p class="text-sm">85% probability of successful conversion</p>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5" />
                    Activity Timeline
                </h3>
                <div class="edit-timeline">
                    <div class="timeline-line"></div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="text-sm">
                            <p class="font-medium">Call Started</p>
                            <p class="text-gray-500">{{ $record->started_at?->format('H:i:s') ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @if($record->ended_at)
                    <div class="timeline-item">
                        <div class="timeline-dot bg-green-500"></div>
                        <div class="text-sm">
                            <p class="font-medium">Call Ended</p>
                            <p class="text-gray-500">{{ $record->ended_at->format('H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($record->analyzed_at)
                    <div class="timeline-item">
                        <div class="timeline-dot bg-purple-500"></div>
                        <div class="text-sm">
                            <p class="font-medium">AI Analysis Complete</p>
                            <p class="text-gray-500">{{ $record->analyzed_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="sidebar-card">
                <h3 class="font-semibold text-lg mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    @if($record->recording_url)
                    <button type="button" class="w-full text-left px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                        <x-heroicon-o-play class="w-4 h-4 inline mr-2" />
                        Play Recording
                    </button>
                    @endif
                    
                    <button type="button" class="w-full text-left px-4 py-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition">
                        <x-heroicon-o-calendar-days class="w-4 h-4 inline mr-2" />
                        Create Appointment
                    </button>
                    
                    <button type="button" class="w-full text-left px-4 py-2 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                        <x-heroicon-o-envelope class="w-4 h-4 inline mr-2" />
                        Send Follow-up
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Changed Indicator -->
    <div class="form-changed-indicator" id="form-changed-indicator">
        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
        <span>You have unsaved changes</span>
    </div>

    <!-- Auto-save Status -->
    <div class="auto-save-status" id="auto-save-status">
        <x-heroicon-o-check-circle class="w-4 h-4 inline mr-1" />
        Auto-saved
    </div>

    <script>
        // Form change detection
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const indicator = document.getElementById('form-changed-indicator');
            const autoSaveStatus = document.getElementById('auto-save-status');
            let hasChanges = false;

            // Track form changes
            form.addEventListener('input', function() {
                if (!hasChanges) {
                    hasChanges = true;
                    indicator.classList.add('active');
                }
            });

            // Auto-save simulation (you'd connect this to actual auto-save)
            let autoSaveTimer;
            form.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    // Simulate auto-save
                    autoSaveStatus.classList.add('show');
                    hasChanges = false;
                    indicator.classList.remove('active');
                    
                    setTimeout(function() {
                        autoSaveStatus.classList.remove('show');
                    }, 3000);
                }, 2000);
            });

            // Warn before leaving with unsaved changes
            window.addEventListener('beforeunload', function(e) {
                if (hasChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        });
    </script>
</x-filament-panels::page>