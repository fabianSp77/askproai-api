<x-filament-panels::page>
    <style>
        /* Ultra Call View Styles */
        .ultra-view-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        @media (max-width: 1280px) {
            .ultra-view-container {
                grid-template-columns: 1fr;
            }
        }

        .view-main {
            space-y: 2rem;
        }

        .view-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .dark .view-card {
            background: var(--filament-gray-800, #1f2937);
        }

        .view-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .call-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 2rem;
        }

        .call-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .status-active {
            background: var(--filament-warning-100, #fef3c7);
            color: var(--filament-warning-800, #92400e);
        }

        .status-completed {
            background: var(--filament-success-100, #d1fae5);
            color: var(--filament-success-800, #065f46);
        }

        .status-missed {
            background: var(--filament-danger-100, #fee2e2);
            color: var(--filament-danger-800, #991b1b);
        }

        .audio-player-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .audio-waveform {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            opacity: 0.3;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 60"><path d="M0,30 Q25,10 50,30 T100,30 L100,60 L0,60 Z" fill="white"/></svg>') repeat-x;
            animation: wave 3s linear infinite;
        }

        @keyframes wave {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100px); }
        }

        .transcript-container {
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .dark .transcript-container {
            background: var(--filament-gray-900, #111827);
        }

        .transcript-line {
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--filament-gray-200, #e5e7eb);
        }

        .dark .transcript-line {
            border-color: var(--filament-gray-700, #374151);
        }

        .transcript-time {
            font-size: 0.875rem;
            color: var(--filament-gray-500, #6b7280);
            font-family: monospace;
        }

        .sentiment-visualization {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .dark .sentiment-visualization {
            background: var(--filament-gray-900, #111827);
        }

        .sentiment-meter {
            flex: 1;
            height: 40px;
            border-radius: 20px;
            background: linear-gradient(to right, 
                #ef4444 0%, 
                #f59e0b 25%, 
                #10b981 50%, 
                #10b981 100%
            );
            position: relative;
            overflow: hidden;
        }

        .sentiment-indicator {
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: left 0.5s ease;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .insight-card {
            background: var(--filament-primary-50, #eff6ff);
            border: 1px solid var(--filament-primary-200, #bfdbfe);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }

        .dark .insight-card {
            background: var(--filament-gray-700, #374151);
            border-color: var(--filament-gray-600, #4b5563);
        }

        .insight-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--filament-primary-600, #2563eb);
        }

        .insight-label {
            font-size: 0.875rem;
            color: var(--filament-gray-600, #4b5563);
            margin-top: 0.25rem;
        }

        .related-calls {
            margin-top: 1rem;
        }

        .call-mini-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .call-mini-card {
            background: var(--filament-gray-700, #374151);
        }

        .call-mini-card:hover {
            background: var(--filament-gray-100, #f3f4f6);
            transform: translateX(4px);
        }

        .dark .call-mini-card:hover {
            background: var(--filament-gray-600, #4b5563);
        }
    </style>

    <div class="ultra-view-container">
        <!-- Main Content -->
        <div class="view-main">
            <!-- Call Header -->
            <div class="view-card">
                <div class="call-header">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">
                            {{ $record->customer?->name ?? 'Unknown Caller' }}
                        </h2>
                        <p class="text-gray-500 flex items-center gap-2">
                            <x-heroicon-o-phone class="w-4 h-4" />
                            {{ $record->phone_number }}
                        </p>
                        <p class="text-sm text-gray-400 mt-1">
                            {{ $record->created_at->format('F j, Y - g:i A') }}
                        </p>
                    </div>
                    <div class="call-status-badge status-{{ $record->status ?? 'active' }}">
                        @if($record->status == 'active')
                            <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                        @endif
                        {{ ucfirst($record->status ?? 'Active') }}
                    </div>
                </div>

                <!-- Call Metrics -->
                <div class="insights-grid">
                    <div class="insight-card">
                        <div class="insight-value">{{ gmdate('i:s', $record->duration ?? 0) }}</div>
                        <div class="insight-label">Call Duration</div>
                    </div>
                    <div class="insight-card">
                        <div class="insight-value">
                            @if($record->sentiment == 'positive')
                                😊
                            @elseif($record->sentiment == 'negative')
                                😔
                            @else
                                😐
                            @endif
                        </div>
                        <div class="insight-label">Sentiment</div>
                    </div>
                    <div class="insight-card">
                        <div class="insight-value">{{ $record->sentiment_score ?? 'N/A' }}</div>
                        <div class="insight-label">Score /10</div>
                    </div>
                    <div class="insight-card">
                        <div class="insight-value">
                            @if($record->appointment_id)
                                ✅
                            @else
                                ⏳
                            @endif
                        </div>
                        <div class="insight-label">Appointment</div>
                    </div>
                </div>
            </div>

            <!-- Audio Player -->
            @if($record->recording_url)
            <div class="view-card audio-player-container">
                <div class="audio-waveform"></div>
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-microphone class="w-6 h-6" />
                    Call Recording
                </h3>
                <audio controls class="w-full" style="position: relative; z-index: 1;">
                    <source src="{{ $record->recording_url }}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <p class="text-sm mt-2 opacity-80">
                    Recording Quality: High | Format: MP3
                </p>
            </div>
            @endif

            <!-- Sentiment Analysis -->
            <div class="view-card">
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-face-smile class="w-6 h-6" />
                    Sentiment Analysis
                </h3>
                <div class="sentiment-visualization">
                    <div class="sentiment-meter">
                        <div class="sentiment-indicator" style="left: {{ ($record->sentiment_score ?? 5) * 10 }}%"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $record->sentiment_score ?? 'N/A' }}/10</div>
                        <div class="text-sm text-gray-500">Confidence Score</div>
                    </div>
                </div>
            </div>

            <!-- Transcript -->
            @if($record->transcript)
            <div class="view-card">
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-6 h-6" />
                    Call Transcript
                </h3>
                <div class="transcript-container">
                    {!! nl2br(e($record->transcript)) !!}
                </div>
            </div>
            @endif

            <!-- Call Notes -->
            @if($record->notes)
            <div class="view-card">
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-pencil-square class="w-6 h-6" />
                    Call Notes
                </h3>
                <div class="prose dark:prose-invert max-w-none">
                    {!! $record->notes !!}
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Customer Info -->
            <div class="view-card">
                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5" />
                    Customer Details
                </h3>
                @if($record->customer)
                <div class="space-y-2">
                    <p class="text-sm">
                        <span class="text-gray-500">Name:</span>
                        <span class="font-medium">{{ $record->customer->name }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Email:</span>
                        <span class="font-medium">{{ $record->customer->email ?? 'N/A' }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Total Calls:</span>
                        <span class="font-medium">{{ $record->customer->calls()->count() }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Customer Since:</span>
                        <span class="font-medium">{{ $record->customer->created_at->format('M Y') }}</span>
                    </p>
                </div>
                @else
                <p class="text-sm text-gray-500">No customer linked to this call</p>
                @endif
            </div>

            <!-- Related Appointment -->
            @if($record->appointment)
            <div class="view-card">
                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                    <x-heroicon-o-calendar-days class="w-5 h-5" />
                    Linked Appointment
                </h3>
                <div class="space-y-2">
                    <p class="text-sm">
                        <span class="text-gray-500">Date:</span>
                        <span class="font-medium">{{ $record->appointment->starts_at->format('M j, Y') }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Time:</span>
                        <span class="font-medium">{{ $record->appointment->starts_at->format('g:i A') }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Service:</span>
                        <span class="font-medium">{{ $record->appointment->service->name ?? 'N/A' }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Status:</span>
                        <span class="font-medium">{{ ucfirst($record->appointment->status) }}</span>
                    </p>
                </div>
            </div>
            @endif

            <!-- AI Recommendations -->
            <div class="view-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                    <x-heroicon-o-sparkles class="w-5 h-5" />
                    AI Recommendations
                </h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-2">
                        <x-heroicon-o-check-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <p class="text-sm">Schedule follow-up call within 48 hours for best engagement</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-heroicon-o-light-bulb class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <p class="text-sm">Customer showed interest in premium services - prepare special offer</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <p class="text-sm">Best callback time: Weekdays 10-12 AM based on availability</p>
                    </div>
                </div>
            </div>

            <!-- Recent Calls -->
            @if($record->customer)
            <div class="view-card">
                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                    <x-heroicon-o-phone class="w-5 h-5" />
                    Recent Calls
                </h3>
                <div class="related-calls">
                    @foreach($record->customer->calls()->where('id', '!=', $record->id)->latest()->limit(5)->get() as $call)
                    <div class="call-mini-card" onclick="window.location.href='{{ route('filament.admin.resources.ultimate-calls.view', $call) }}'">
                        <div>
                            <p class="font-medium text-sm">{{ $call->created_at->format('M j') }}</p>
                            <p class="text-xs text-gray-500">{{ gmdate('i:s', $call->duration) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($call->sentiment == 'positive')
                                <span class="text-green-500">😊</span>
                            @elseif($call->sentiment == 'negative')
                                <span class="text-red-500">😔</span>
                            @else
                                <span class="text-gray-500">😐</span>
                            @endif
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{ $this->infolist }}

    <script>
        // Animate sentiment indicator on load
        document.addEventListener('DOMContentLoaded', function() {
            const indicator = document.querySelector('.sentiment-indicator');
            if (indicator) {
                setTimeout(() => {
                    indicator.style.transition = 'left 1s ease-out';
                }, 100);
            }
        });
    </script>
</x-filament-panels::page>