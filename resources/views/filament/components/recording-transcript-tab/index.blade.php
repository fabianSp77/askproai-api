{{--
    Recording & Transcript Tab - Wavesurfer.js Integration
    v9.0 - Real Audio Waveform with Wavesurfer.js
--}}

@php
    $recordingUrl = $recordingUrl ?? null;
    $durationSec = $durationSec ?? 0;
    $callId = $callId ?? uniqid();
    $transcript = $transcript ?? '';
    $transcriptObject = $transcriptObject ?? [];

    if (is_string($transcriptObject) && !empty($transcriptObject)) {
        $transcriptObject = json_decode($transcriptObject, true) ?? [];
    }
    if (!is_array($transcriptObject)) {
        $transcriptObject = [];
    }

    $messages = [];
    if (!empty($transcriptObject)) {
        foreach ($transcriptObject as $item) {
            $role = strtolower($item['role'] ?? 'system');
            $speaker = 'system';
            if (in_array($role, ['agent', 'assistant', 'bot', 'ai'])) {
                $speaker = 'agent';
            } elseif (in_array($role, ['user', 'customer', 'kunde', 'human'])) {
                $speaker = 'customer';
            }
            $startTime = null;
            $endTime = null;
            if (!empty($item['words']) && is_array($item['words'])) {
                $startTime = $item['words'][0]['start'] ?? null;
                $lastWord = end($item['words']);
                $endTime = $lastWord['end'] ?? null;
            }
            $messages[] = [
                'speaker' => $speaker,
                'content' => $item['content'] ?? '',
                'startTime' => $startTime,
                'endTime' => $endTime
            ];
        }
    } elseif (!empty($transcript)) {
        $lines = explode("\n", $transcript);
        $currentSpeaker = 'system';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $speaker = $currentSpeaker;
            $content = $line;
            if (preg_match('/^(Agent|Kunde|Customer|User|Assistant)[\:\s]/i', $line, $matches)) {
                $detectedSpeaker = strtolower($matches[1]);
                if (in_array($detectedSpeaker, ['agent', 'assistant'])) $speaker = 'agent';
                elseif (in_array($detectedSpeaker, ['kunde', 'customer', 'user'])) $speaker = 'customer';
                $content = trim(preg_replace('/^[^\:\s]+[\:\s]+/', '', $line));
                $currentSpeaker = $speaker;
            }
            $messages[] = ['speaker' => $speaker, 'content' => $content, 'startTime' => null, 'endTime' => null];
        }
    }

    $wordCount = array_sum(array_map(fn($m) => str_word_count($m['content']), $messages));
    $componentId = 'wavesurfer-' . $callId . '-' . substr(md5(uniqid()), 0, 6);
    $hasRecording = !empty($recordingUrl);
    $hasTranscript = count($messages) > 0;
@endphp

{{-- Single Root Element Wrapper for Livewire Compatibility --}}
<div class="recording-transcript-wrapper">

{{-- Wavesurfer.js Styles --}}
<style>
    /* Transcript Visual Focus (Spotify-Style) */
    .messages-playing .msg {
        opacity: 0.45;
        filter: blur(0.4px);
        transform: scale(0.97);
    }
    .messages-playing .msg.active {
        opacity: 1;
        filter: none;
        transform: scale(1.01);
        z-index: 10;
    }
    .msg {
        transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                    filter 0.25s ease,
                    transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Wavesurfer Container */
    .wavesurfer-container {
        position: relative;
        background: linear-gradient(to bottom,
            rgba(249, 250, 251, 0.9) 0%,
            rgba(243, 244, 246, 0.7) 50%,
            rgba(249, 250, 251, 0.9) 100%);
        border-radius: 1rem;
        overflow: hidden;
        cursor: pointer;
    }
    .dark .wavesurfer-container {
        background: linear-gradient(to bottom,
            rgba(17, 24, 39, 0.8) 0%,
            rgba(31, 41, 55, 0.6) 50%,
            rgba(17, 24, 39, 0.8) 100%);
    }

    /* Loading State */
    .wavesurfer-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(249, 250, 251, 0.95);
        backdrop-filter: blur(4px);
        z-index: 10;
        transition: opacity 0.3s ease;
    }
    .dark .wavesurfer-loading {
        background: rgba(17, 24, 39, 0.95);
    }
    .wavesurfer-loading.hidden {
        opacity: 0;
        pointer-events: none;
    }

    /* Hover Time Tooltip */
    .wavesurfer-hover-time {
        position: absolute;
        top: -2.5rem;
        padding: 0.375rem 0.75rem;
        background: rgba(17, 24, 39, 0.95);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        opacity: 0;
        pointer-events: none;
        transform: translateX(-50%);
        transition: opacity 0.15s ease;
        z-index: 30;
    }
    .dark .wavesurfer-hover-time {
        background: rgba(55, 65, 81, 0.95);
    }
    .wavesurfer-hover-time::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 12px;
        height: 12px;
        background: inherit;
    }

    /* Error State */
    .wavesurfer-error {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        height: 100%;
        color: #ef4444;
    }
    .wavesurfer-error.visible {
        display: flex;
    }
    .dark .wavesurfer-error {
        color: #f87171;
    }

    /* Screen reader only */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }

    /* Skeleton Loader Animation */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .skeleton-wave {
        position: relative;
        overflow: hidden;
        background: linear-gradient(90deg,
            rgba(156, 163, 175, 0.1) 0%,
            rgba(156, 163, 175, 0.3) 20%,
            rgba(156, 163, 175, 0.1) 40%);
        border-radius: 4px;
    }
    .dark .skeleton-wave {
        background: linear-gradient(90deg,
            rgba(107, 114, 128, 0.1) 0%,
            rgba(107, 114, 128, 0.3) 20%,
            rgba(107, 114, 128, 0.1) 40%);
    }
    .skeleton-wave::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
            transparent 0%,
            rgba(255, 255, 255, 0.2) 50%,
            transparent 100%);
        animation: shimmer 1.5s infinite;
    }
    .dark .skeleton-wave::after {
        background: linear-gradient(90deg,
            transparent 0%,
            rgba(255, 255, 255, 0.05) 50%,
            transparent 100%);
    }

    /* Keyboard Shortcut Hint */
    .keyboard-hints {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 0.7rem;
        color: #9ca3af;
    }
    .keyboard-hint {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .keyboard-hint kbd {
        padding: 0.125rem 0.375rem;
        background: rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.65rem;
    }
    .dark .keyboard-hint kbd {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.1);
    }

    /* Keyword Badges */
    .keyword-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        font-size: 0.65rem;
        font-weight: 500;
        border-radius: 9999px;
        margin-left: 0.5rem;
    }
    .keyword-termin { background: #dbeafe; color: #1e40af; }
    .keyword-absage { background: #fee2e2; color: #991b1b; }
    .keyword-problem { background: #fef3c7; color: #92400e; }
    .keyword-beschwerde { background: #fce7f3; color: #9d174d; }
    .keyword-bestaetigung { background: #d1fae5; color: #065f46; }
    .dark .keyword-termin { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
    .dark .keyword-absage { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .dark .keyword-problem { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .dark .keyword-beschwerde { background: rgba(236, 72, 153, 0.2); color: #f9a8d4; }
    .dark .keyword-bestaetigung { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }

    /* Keyword Summary Bar */
    .keyword-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(249, 250, 251, 0.5);
        border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    }
    .dark .keyword-summary {
        background: rgba(31, 41, 55, 0.5);
        border-color: rgba(55, 65, 81, 0.5);
    }
    .keyword-summary-item {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.7rem;
        color: #6b7280;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        transition: background 0.15s;
    }
    .keyword-summary-item:hover {
        background: rgba(0,0,0,0.05);
    }
    .dark .keyword-summary-item:hover {
        background: rgba(255,255,255,0.05);
    }
    .keyword-summary-item.active {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
    }
    .dark .keyword-summary-item.active {
        background: rgba(59, 130, 246, 0.2);
        color: #93c5fd;
    }
</style>

<div class="recording-transcript-tab" id="{{ $componentId }}" wire:ignore>
    {{-- SIDE-BY-SIDE LAYOUT: Player links (sticky), Transcript rechts --}}
    <div class="lg:grid lg:grid-cols-12 lg:gap-6 space-y-6 lg:space-y-0">

        {{-- LEFT COLUMN: Audio Player (Sticky on Desktop) --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="lg:sticky lg:top-4">
                @if($hasRecording)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Header --}}
                    <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Anrufaufnahme</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Dauer: {{ gmdate('i:s', $durationSec) }}</p>
                            </div>
                        </div>
                        <a href="{{ $recordingUrl }}" download class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span class="hidden sm:inline">Download</span>
                        </a>
                    </div>

                    {{-- Player Controls --}}
                    <div class="p-4 space-y-4">
                        {{-- Wavesurfer.js Waveform Container --}}
                        <div class="wavesurfer-container relative h-24 rounded-2xl shadow-inner ring-1 ring-gray-200/50 dark:ring-gray-700/30"
                             data-waveform="{{ $componentId }}"
                             role="slider"
                             aria-label="Audio-Fortschritt"
                             aria-valuemin="0"
                             aria-valuemax="{{ $durationSec }}"
                             aria-valuenow="0"
                             aria-valuetext="0:00 von {{ gmdate('i:s', $durationSec) }}"
                             tabindex="0">

                            {{-- Waveform will be rendered here by Wavesurfer.js --}}
                            <div id="waveform-{{ $componentId }}" class="w-full h-full"></div>

                            {{-- Error State (hidden by default, shown via JS) --}}
                            <div class="wavesurfer-error absolute inset-0" data-error="{{ $componentId }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-sm font-medium">Audio konnte nicht geladen werden</span>
                            </div>

                            {{-- Hover Time Tooltip --}}
                            <div class="wavesurfer-hover-time" data-hover-time="{{ $componentId }}">0:00</div>

                            {{-- Loading Overlay with Skeleton --}}
                            <div class="wavesurfer-loading" data-loading="{{ $componentId }}">
                                <div class="flex flex-col items-center gap-3 w-full px-8">
                                    {{-- Skeleton Waveform Bars --}}
                                    <div class="flex items-end justify-center gap-1 w-full h-12">
                                        @for($i = 0; $i < 24; $i++)
                                        <div class="skeleton-wave" style="width: 6px; height: {{ rand(20, 100) }}%; animation-delay: {{ $i * 0.05 }}s"></div>
                                        @endfor
                                    </div>
                                    {{-- Loading Text --}}
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Waveform laden...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Screen Reader Announcements --}}
                        <div aria-live="polite" aria-atomic="true" class="sr-only" data-announcer="{{ $componentId }}"></div>

                        {{-- Controls Row --}}
                        <div class="flex items-center gap-3">
                            {{-- Skip Back --}}
                            <button type="button"
                                    data-skip-back="{{ $componentId }}"
                                    class="p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all duration-150 transform hover:scale-105 active:scale-90"
                                    title="10 Sekunden zurück"
                                    aria-label="10 Sekunden zurückspulen">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>
                            </button>

                            {{-- Play/Pause --}}
                            <button type="button"
                                    data-playpause="{{ $componentId }}"
                                    class="w-14 h-14 flex items-center justify-center bg-gradient-to-br from-primary-400 to-primary-600 hover:from-primary-500 hover:to-primary-700 text-white rounded-full shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all duration-200 transform hover:scale-105 active:scale-95"
                                    aria-label="Wiedergabe starten"
                                    aria-pressed="false">
                                <svg data-icon-play class="w-7 h-7 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <svg data-icon-pause class="w-7 h-7 hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                            </button>

                            {{-- Skip Forward --}}
                            <button type="button"
                                    data-skip-forward="{{ $componentId }}"
                                    class="p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all duration-150 transform hover:scale-105 active:scale-90"
                                    title="10 Sekunden vor"
                                    aria-label="10 Sekunden vorspulen">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>
                            </button>

                            {{-- Time Display --}}
                            <div class="flex items-center gap-1 text-sm font-mono text-gray-600 dark:text-gray-300 min-w-[90px]">
                                <span data-time-current="{{ $componentId }}">0:00</span>
                                <span class="text-gray-400">/</span>
                                <span data-time-total="{{ $componentId }}">{{ gmdate('i:s', $durationSec) }}</span>
                            </div>

                            <div class="flex-1"></div>

                            {{-- Speed Control with ARIA menu --}}
                            <div class="relative">
                                <button
                                    type="button"
                                    data-speed-btn="{{ $componentId }}"
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-label="Wiedergabegeschwindigkeit"
                                    class="px-2 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                >
                                    <span data-speed-label="{{ $componentId }}">1x</span>
                                </button>
                                <div
                                    role="menu"
                                    aria-label="Wiedergabegeschwindigkeit"
                                    data-speed-menu="{{ $componentId }}"
                                    class="hidden absolute bottom-full right-0 mb-2 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-10"
                                >
                                    @foreach([0.5, 0.75, 1, 1.25, 1.5, 2] as $speed)
                                    <button type="button" role="menuitemradio" aria-checked="{{ $speed == 1 ? 'true' : 'false' }}" data-speed-option="{{ $speed }}" class="block w-full px-4 py-1.5 text-sm text-left text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $speed == 1 ? 'font-semibold' : '' }}">{{ $speed }}x</button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Volume Control with ARIA --}}
                            <div class="relative">
                                <button
                                    type="button"
                                    data-volume-btn="{{ $componentId }}"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    aria-label="Lautstärke anpassen"
                                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                >
                                    <svg data-icon-volume-high class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                    <svg data-icon-volume-muted class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                                </button>
                                <div
                                    role="dialog"
                                    aria-label="Lautstärkeregler"
                                    data-volume-slider="{{ $componentId }}"
                                    class="hidden absolute bottom-full right-0 mb-2 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-10"
                                >
                                    <label for="volume-{{ $componentId }}" class="sr-only">Lautstärke</label>
                                    <input
                                        type="range"
                                        id="volume-{{ $componentId }}"
                                        min="0"
                                        max="1"
                                        step="0.1"
                                        value="1"
                                        class="w-24 h-2 accent-primary-500"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="100"
                                        aria-valuetext="100%"
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- Keyboard Shortcuts Hint --}}
                        <div class="keyboard-hints pt-2 border-t border-gray-100 dark:border-gray-700 mt-2">
                            <span class="keyboard-hint"><kbd>Space</kbd> Play/Pause</span>
                            <span class="keyboard-hint"><kbd>←</kbd><kbd>→</kbd> Spulen</span>
                            <span class="keyboard-hint"><kbd>M</kbd> Mute</span>
                            <span class="keyboard-hint"><kbd>/</kbd> Suche</span>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-1">Keine Aufnahme</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Für diesen Anruf liegt keine Audioaufnahme vor.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- RIGHT COLUMN: Transcript --}}
        <div class="lg:col-span-7">
            @if($hasTranscript)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Transcript Header --}}
                <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Gesprächsverlauf</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($messages) }} Nachrichten · {{ $wordCount }} Wörter</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            {{-- Speaker Filter with ARIA radiogroup --}}
                            <div
                                role="radiogroup"
                                aria-label="Sprecher filtern"
                                class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg"
                                data-speaker-filter="{{ $componentId }}"
                            >
                                <button type="button" role="radio" aria-checked="true" data-filter-option="all" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm">Alle</button>
                                <button type="button" role="radio" aria-checked="false" data-filter-option="agent" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors text-gray-600 dark:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-600/50">
                                    <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1" aria-hidden="true"></span>Agent
                                </button>
                                <button type="button" role="radio" aria-checked="false" data-filter-option="customer" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors text-gray-600 dark:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-600/50">
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1" aria-hidden="true"></span>Kunde
                                </button>
                            </div>

                            {{-- Search with accessible label --}}
                            <div class="relative flex-1 sm:flex-none">
                                <label for="transcript-search-{{ $componentId }}" class="sr-only">
                                    Transkript durchsuchen
                                </label>
                                <input
                                    type="search"
                                    id="transcript-search-{{ $componentId }}"
                                    data-search="{{ $componentId }}"
                                    placeholder="Suchen..."
                                    aria-describedby="search-hint-{{ $componentId }}"
                                    class="w-full sm:w-40 pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                >
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <span id="search-hint-{{ $componentId }}" class="sr-only">
                                    Drücken Sie Eingabe zum Suchen, Escape zum Leeren
                                </span>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center gap-1">
                                {{-- Copy --}}
                                <button type="button" data-copy="{{ $componentId }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors" title="Transkript kopieren">
                                    <svg class="w-4 h-4" data-icon="copy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <svg class="w-4 h-4 hidden" data-icon="check" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>

                                {{-- Export TXT --}}
                                <button type="button" data-export-txt="{{ $componentId }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors" title="Als TXT exportieren">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="hidden lg:inline text-xs">.txt</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Keyword Summary Bar (populated by JS) --}}
                <div class="keyword-summary hidden" data-keyword-summary="{{ $componentId }}"></div>

                {{-- Messages Container --}}
                <div data-messages="{{ $componentId }}" class="p-4 space-y-4 overflow-y-auto" style="max-height: calc(100vh - 280px); min-height: 400px; scrollbar-width: thin;">
                    @foreach ($messages as $idx => $message)
                        @if($message['speaker'] === 'agent')
                        <div class="msg flex items-start gap-3 max-w-[90%] sm:max-w-[85%] transition-all duration-300 rounded-xl" data-msg-idx="{{ $idx }}" data-text="{{ e($message['content']) }}" data-start="{{ $message['startTime'] ?? '' }}" data-end="{{ $message['endTime'] ?? '' }}" @if($message['startTime'] !== null) style="cursor: pointer" @endif>
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Agent</span>
                                    @if($message['startTime'] !== null)<span class="text-xs text-gray-400">{{ gmdate('i:s', (int)$message['startTime']) }}</span>@endif
                                </div>
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-2xl rounded-tl-sm">
                                    <p class="msg-text text-sm text-gray-800 dark:text-gray-100 whitespace-pre-wrap break-words leading-relaxed">{{ $message['content'] }}</p>
                                </div>
                            </div>
                        </div>
                        @elseif($message['speaker'] === 'customer')
                        <div class="msg flex items-start gap-3 max-w-[90%] sm:max-w-[85%] ml-auto flex-row-reverse transition-all duration-300 rounded-xl" data-msg-idx="{{ $idx }}" data-text="{{ e($message['content']) }}" data-start="{{ $message['startTime'] ?? '' }}" data-end="{{ $message['endTime'] ?? '' }}" @if($message['startTime'] !== null) style="cursor: pointer" @endif>
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-end gap-2 mb-1">
                                    @if($message['startTime'] !== null)<span class="text-xs text-gray-400">{{ gmdate('i:s', (int)$message['startTime']) }}</span>@endif
                                    <span class="text-xs font-medium text-green-600 dark:text-green-400">Kunde</span>
                                </div>
                                <div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-2xl rounded-tr-sm">
                                    <p class="msg-text text-sm text-gray-800 dark:text-gray-100 whitespace-pre-wrap break-words leading-relaxed">{{ $message['content'] }}</p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="msg flex justify-center transition-all duration-300" data-msg-idx="{{ $idx }}" data-text="{{ e($message['content']) }}" data-start="{{ $message['startTime'] ?? '' }}" data-end="{{ $message['endTime'] ?? '' }}">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="msg-text text-sm text-gray-600 dark:text-gray-300">{{ $message['content'] }}</span>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-1">Kein Transkript</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Für diesen Anruf liegt kein Gesprächstranskript vor.</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Wavesurfer.js CDN - Pinned to exact version for stability --}}
<script src="https://unpkg.com/wavesurfer.js@7.8.8"></script>

<script>
(function(){
    var id = '{{ $componentId }}';
    var audioUrl = @json($recordingUrl);

    if (window['_ws_init_' + id]) return;
    window['_ws_init_' + id] = 1;

    function init() {
        var container = document.getElementById(id);
        if (!container || !audioUrl) return;

        // Elements
        var waveformEl = document.getElementById('waveform-' + id);
        var loadingEl = container.querySelector('[data-loading="' + id + '"]');
        var errorEl = container.querySelector('[data-error="' + id + '"]');
        var hoverTimeEl = container.querySelector('[data-hover-time="' + id + '"]');
        var waveformContainer = container.querySelector('[data-waveform="' + id + '"]');
        var playPauseBtn = container.querySelector('[data-playpause="' + id + '"]');
        var iconPlay = playPauseBtn ? playPauseBtn.querySelector('[data-icon-play]') : null;
        var iconPause = playPauseBtn ? playPauseBtn.querySelector('[data-icon-pause]') : null;
        var skipBackBtn = container.querySelector('[data-skip-back="' + id + '"]');
        var skipForwardBtn = container.querySelector('[data-skip-forward="' + id + '"]');
        var timeCurrent = container.querySelector('[data-time-current="' + id + '"]');
        var timeTotal = container.querySelector('[data-time-total="' + id + '"]');
        var speedBtn = container.querySelector('[data-speed-btn="' + id + '"]');
        var speedMenu = container.querySelector('[data-speed-menu="' + id + '"]');
        var speedLabel = container.querySelector('[data-speed-label="' + id + '"]');
        var volumeBtn = container.querySelector('[data-volume-btn="' + id + '"]');
        var volumeSlider = container.querySelector('[data-volume-slider="' + id + '"]');
        var volumeInput = volumeSlider ? volumeSlider.querySelector('input') : null;
        var iconVolHigh = volumeBtn ? volumeBtn.querySelector('[data-icon-volume-high]') : null;
        var iconVolMuted = volumeBtn ? volumeBtn.querySelector('[data-icon-volume-muted]') : null;
        var searchInput = container.querySelector('[data-search="' + id + '"]');
        var copyBtn = container.querySelector('[data-copy="' + id + '"]');
        var messagesContainer = container.querySelector('[data-messages="' + id + '"]');
        var announcer = container.querySelector('[data-announcer="' + id + '"]');

        var activeIdx = -1;
        var autoScroll = true;
        var scrollTimeout = null;

        // Retry configuration for error resilience
        var retryCount = 0;
        var maxRetries = 3;
        var retryDelays = [1000, 2000, 3000]; // Progressive backoff

        // Detect dark mode
        var isDarkMode = document.documentElement.classList.contains('dark') ||
                         document.body.classList.contains('dark') ||
                         window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Loading state management
        function updateLoadingText(text) {
            if (loadingEl) {
                var span = loadingEl.querySelector('span');
                if (span) span.textContent = text;
            }
        }

        // Create Wavesurfer instance (extracted for retry capability)
        var wavesurfer = null;

        function createWavesurfer() {
            if (wavesurfer) {
                try { wavesurfer.destroy(); } catch(e) {}
            }

            wavesurfer = WaveSurfer.create({
                container: waveformEl,
                waveColor: isDarkMode ? '#6b7280' : '#9ca3af',
                progressColor: isDarkMode ? '#38bdf8' : '#0ea5e9',
                cursorColor: isDarkMode ? '#f8fafc' : '#1e293b',
                cursorWidth: 2,
                barWidth: 3,
                barGap: 2,
                barRadius: 3,
                height: 96,
                normalize: true,
                backend: 'WebAudio',
                url: audioUrl
            });

            // Attach all event handlers
            attachWavesurferEvents(wavesurfer);

            return wavesurfer;
        }

        // Utility functions
        function formatTime(sec) {
            var m = Math.floor(sec / 60);
            var s = Math.floor(sec % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        // Optimized announcer with 100ms clear time for screen readers
        function announce(message) {
            if (announcer) {
                announcer.textContent = message;
                setTimeout(function() { announcer.textContent = ''; }, 100);
            }
        }

        function updateVolumeIcon(vol) {
            if (!iconVolHigh || !iconVolMuted) return;
            if (vol == 0) {
                iconVolHigh.classList.add('hidden');
                iconVolMuted.classList.remove('hidden');
            } else {
                iconVolHigh.classList.remove('hidden');
                iconVolMuted.classList.add('hidden');
            }
        }

        // Attach all Wavesurfer event handlers
        function attachWavesurferEvents(ws) {
            ws.on('ready', function(duration) {
                console.log('[Wavesurfer] Ready, duration:', duration);
                retryCount = 0; // Reset retry count on success
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.remove('visible');
                if (timeTotal) timeTotal.textContent = formatTime(duration);
                announce('Audio bereit');
            });

            ws.on('loading', function(percent) {
                console.log('[Wavesurfer] Loading:', percent + '%');
                updateLoadingText('Waveform laden... ' + percent + '%');
            });

            // Error handling with retry logic
            ws.on('error', function(error) {
                console.error('[Wavesurfer] Error:', error, 'Retry:', retryCount + 1 + '/' + maxRetries);

                if (retryCount < maxRetries) {
                    var delay = retryDelays[retryCount] || 3000;
                    updateLoadingText('Verbindungsfehler. Erneuter Versuch in ' + (delay / 1000) + 's...');
                    announce('Ladefehler. Erneuter Versuch ' + (retryCount + 1) + ' von ' + maxRetries);

                    setTimeout(function() {
                        retryCount++;
                        console.log('[Wavesurfer] Retrying... Attempt ' + retryCount);
                        updateLoadingText('Erneuter Versuch ' + retryCount + ' von ' + maxRetries + '...');
                        createWavesurfer();
                    }, delay);
                } else {
                    // Max retries reached - show error
                    if (loadingEl) loadingEl.classList.add('hidden');
                    if (errorEl) {
                        errorEl.classList.add('visible');
                        var errorSpan = errorEl.querySelector('span');
                        if (errorSpan) {
                            errorSpan.textContent = 'Audio konnte nach ' + maxRetries + ' Versuchen nicht geladen werden';
                        }
                    }
                    announce('Audio konnte nicht geladen werden');
                }
            });

            ws.on('play', function() {
                if (iconPlay) iconPlay.classList.add('hidden');
                if (iconPause) iconPause.classList.remove('hidden');
                if (playPauseBtn) {
                    playPauseBtn.setAttribute('aria-pressed', 'true');
                    playPauseBtn.setAttribute('aria-label', 'Pausieren');
                }
                if (messagesContainer) messagesContainer.classList.add('messages-playing');
                announce('Wiedergabe gestartet');
            });

            ws.on('pause', function() {
                if (iconPlay) iconPlay.classList.remove('hidden');
                if (iconPause) iconPause.classList.add('hidden');
                if (playPauseBtn) {
                    playPauseBtn.setAttribute('aria-pressed', 'false');
                    playPauseBtn.setAttribute('aria-label', 'Wiedergabe starten');
                }
                if (messagesContainer) messagesContainer.classList.remove('messages-playing');
                announce('Pausiert');
            });

            ws.on('finish', function() {
                if (iconPlay) iconPlay.classList.remove('hidden');
                if (iconPause) iconPause.classList.add('hidden');
                if (playPauseBtn) {
                    playPauseBtn.setAttribute('aria-pressed', 'false');
                    playPauseBtn.setAttribute('aria-label', 'Wiedergabe starten');
                }
                if (messagesContainer) messagesContainer.classList.remove('messages-playing');
                announce('Wiedergabe beendet');
            });

            ws.on('timeupdate', function(currentTime) {
                if (timeCurrent) timeCurrent.textContent = formatTime(currentTime);
                updateActiveMessage(currentTime);

                // Update ARIA
                if (waveformContainer) {
                    waveformContainer.setAttribute('aria-valuenow', Math.floor(currentTime));
                    waveformContainer.setAttribute('aria-valuetext', formatTime(currentTime) + ' von ' + formatTime(ws.getDuration() || 0));
                }
            });
        }

        // Initialize Wavesurfer
        wavesurfer = createWavesurfer();

        // Hover time tooltip
        if (waveformContainer && hoverTimeEl) {
            waveformContainer.addEventListener('mousemove', function(e) {
                var duration = wavesurfer.getDuration();
                if (!duration) return;

                var rect = waveformContainer.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var pct = Math.max(0, Math.min(1, x / rect.width));
                var time = pct * duration;

                hoverTimeEl.textContent = formatTime(time);
                hoverTimeEl.style.left = (pct * 100) + '%';
                hoverTimeEl.style.opacity = '1';
            });

            waveformContainer.addEventListener('mouseleave', function() {
                hoverTimeEl.style.opacity = '0';
            });
        }

        // Play/Pause
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', function() {
                wavesurfer.playPause();
            });
        }

        // Skip buttons
        if (skipBackBtn) {
            skipBackBtn.addEventListener('click', function() {
                wavesurfer.skip(-10);
            });
        }
        if (skipForwardBtn) {
            skipForwardBtn.addEventListener('click', function() {
                wavesurfer.skip(10);
            });
        }

        // Speed Control with ARIA management
        if (speedBtn && speedMenu) {
            speedBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var isHidden = speedMenu.classList.toggle('hidden');
                speedBtn.setAttribute('aria-expanded', !isHidden);
            });

            speedMenu.querySelectorAll('[data-speed-option]').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    var speed = parseFloat(this.dataset.speedOption);
                    wavesurfer.setPlaybackRate(speed);
                    if (speedLabel) speedLabel.textContent = speed + 'x';

                    // Update aria-checked on all options
                    speedMenu.querySelectorAll('[data-speed-option]').forEach(function(o) {
                        o.setAttribute('aria-checked', 'false');
                        o.classList.remove('font-semibold');
                    });
                    this.setAttribute('aria-checked', 'true');
                    this.classList.add('font-semibold');

                    speedMenu.classList.add('hidden');
                    speedBtn.setAttribute('aria-expanded', 'false');
                    announce('Geschwindigkeit: ' + speed + 'x');
                });
            });

            document.addEventListener('click', function() {
                speedMenu.classList.add('hidden');
                speedBtn.setAttribute('aria-expanded', 'false');
            });
        }

        // Volume Control with ARIA management
        if (volumeBtn && volumeSlider) {
            volumeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var isHidden = volumeSlider.classList.toggle('hidden');
                volumeBtn.setAttribute('aria-expanded', !isHidden);
            });

            if (volumeInput) {
                volumeInput.addEventListener('input', function() {
                    var vol = parseFloat(this.value);
                    wavesurfer.setVolume(vol);
                    updateVolumeIcon(vol);
                    // Update aria-valuetext for screen readers
                    this.setAttribute('aria-valuenow', Math.round(vol * 100));
                    this.setAttribute('aria-valuetext', Math.round(vol * 100) + '%');
                });
            }

            document.addEventListener('click', function() {
                volumeSlider.classList.add('hidden');
                volumeBtn.setAttribute('aria-expanded', 'false');
            });

            volumeSlider.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // Keyboard navigation
        if (waveformContainer) {
            waveformContainer.addEventListener('keydown', function(e) {
                var duration = wavesurfer.getDuration();
                if (!duration) return;

                var step = duration / 20;
                switch(e.key) {
                    case 'ArrowLeft':
                    case 'ArrowDown':
                        e.preventDefault();
                        wavesurfer.skip(-step);
                        announce(formatTime(wavesurfer.getCurrentTime()));
                        break;
                    case 'ArrowRight':
                    case 'ArrowUp':
                        e.preventDefault();
                        wavesurfer.skip(step);
                        announce(formatTime(wavesurfer.getCurrentTime()));
                        break;
                    case 'Home':
                        e.preventDefault();
                        wavesurfer.setTime(0);
                        announce('Anfang');
                        break;
                    case 'End':
                        e.preventDefault();
                        wavesurfer.setTime(duration);
                        announce('Ende');
                        break;
                    case ' ':
                    case 'Enter':
                        e.preventDefault();
                        wavesurfer.playPause();
                        break;
                }
            });
        }

        // Transcript Search (uses combined applyFilters function)
        if (searchInput && messagesContainer) {
            var searchTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    applyFilters();
                }, 200);
            });
        }

        // Transcript Copy
        if (copyBtn && messagesContainer) {
            copyBtn.addEventListener('click', function() {
                var texts = [];
                messagesContainer.querySelectorAll('.msg').forEach(function(el) {
                    if (el.style.display !== 'none') texts.push(el.dataset.text);
                });
                var text = texts.join('\n\n');
                var copyIcon = copyBtn.querySelector('[data-icon="copy"]');
                var checkIcon = copyBtn.querySelector('[data-icon="check"]');
                var label = copyBtn.querySelector('[data-label]');

                function showSuccess() {
                    if (copyIcon) copyIcon.classList.add('hidden');
                    if (checkIcon) checkIcon.classList.remove('hidden');
                    if (label) label.textContent = 'Kopiert!';
                    copyBtn.classList.add('bg-green-100', 'text-green-700');
                    setTimeout(function() {
                        if (copyIcon) copyIcon.classList.remove('hidden');
                        if (checkIcon) checkIcon.classList.add('hidden');
                        if (label) label.textContent = 'Kopieren';
                        copyBtn.classList.remove('bg-green-100', 'text-green-700');
                    }, 2000);
                }

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(showSuccess).catch(function() {
                        fallbackCopy(text, showSuccess);
                    });
                } else {
                    fallbackCopy(text, showSuccess);
                }
            });

            function fallbackCopy(text, cb) {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); cb(); } catch(e) {}
                document.body.removeChild(ta);
            }
        }

        // Speaker Filter (uses radio role)
        var speakerFilter = container.querySelector('[data-speaker-filter="' + id + '"]');
        var currentSpeakerFilter = 'all';

        if (speakerFilter && messagesContainer) {
            speakerFilter.querySelectorAll('[data-filter-option]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var filter = this.dataset.filterOption;
                    currentSpeakerFilter = filter;

                    // Update button styles and ARIA states
                    speakerFilter.querySelectorAll('[data-filter-option]').forEach(function(b) {
                        b.classList.remove('bg-white', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white', 'shadow-sm');
                        b.classList.add('text-gray-600', 'dark:text-gray-300');
                        b.setAttribute('aria-checked', 'false');
                    });
                    this.classList.add('bg-white', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white', 'shadow-sm');
                    this.classList.remove('text-gray-600', 'dark:text-gray-300');
                    this.setAttribute('aria-checked', 'true');

                    // Apply filter
                    applyFilters();
                    announce('Filter: ' + (filter === 'all' ? 'Alle' : filter === 'agent' ? 'Agent' : 'Kunde'));
                });
            });
        }

        // Combined filter function (speaker + search)
        function applyFilters() {
            if (!messagesContainer) return;
            var searchVal = searchInput ? searchInput.value.trim().toLowerCase() : '';

            messagesContainer.querySelectorAll('.msg').forEach(function(el) {
                var txt = el.querySelector('.msg-text');
                var original = el.dataset.text;
                var msgSpeaker = el.classList.contains('flex-row-reverse') ? 'customer' : (el.classList.contains('justify-center') ? 'system' : 'agent');

                // Check speaker filter
                var speakerMatch = currentSpeakerFilter === 'all' || currentSpeakerFilter === msgSpeaker;

                // Check search filter
                var searchMatch = !searchVal || original.toLowerCase().indexOf(searchVal) > -1;

                if (speakerMatch && searchMatch) {
                    el.style.display = '';
                    // Apply search highlighting if needed
                    if (searchVal && original.toLowerCase().indexOf(searchVal) > -1) {
                        var parts = original.split(new RegExp('(' + searchVal.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'));
                        txt.textContent = '';
                        parts.forEach(function(part) {
                            if (part.toLowerCase() === searchVal) {
                                var mark = document.createElement('mark');
                                mark.className = 'bg-yellow-300 dark:bg-yellow-500 px-0.5 rounded';
                                mark.textContent = part;
                                txt.appendChild(mark);
                            } else {
                                txt.appendChild(document.createTextNode(part));
                            }
                        });
                    } else {
                        txt.textContent = original;
                    }
                } else {
                    el.style.display = 'none';
                }
            });
        }

        // Export as TXT
        var exportTxtBtn = container.querySelector('[data-export-txt="' + id + '"]');

        if (exportTxtBtn && messagesContainer) {
            exportTxtBtn.addEventListener('click', function() {
                var content = [];
                var callId = '{{ $callId }}';
                var duration = '{{ gmdate("i:s", $durationSec) }}';

                // Header
                content.push('='.repeat(50));
                content.push('Gesprächstranskript');
                content.push('Call ID: ' + callId);
                content.push('Dauer: ' + duration);
                content.push('Export: ' + new Date().toLocaleString('de-DE'));
                content.push('='.repeat(50));
                content.push('');

                // Messages
                messagesContainer.querySelectorAll('.msg').forEach(function(el) {
                    if (el.style.display === 'none') return;

                    var text = el.dataset.text;
                    var start = el.dataset.start;
                    var speaker = el.classList.contains('flex-row-reverse') ? 'Kunde' : (el.classList.contains('justify-center') ? 'System' : 'Agent');

                    var timestamp = start ? '[' + formatTime(parseFloat(start)) + '] ' : '';
                    content.push(timestamp + speaker + ':');
                    content.push(text);
                    content.push('');
                });

                // Download
                var blob = new Blob([content.join('\n')], { type: 'text/plain;charset=utf-8' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'transkript-' + callId + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                announce('Transkript exportiert');
            });
        }

        // Keyword Detection & Highlighting
        var keywordSummary = container.querySelector('[data-keyword-summary="' + id + '"]');
        var keywords = {
            'termin': { label: 'Termin', class: 'keyword-termin', colorClass: 'bg-blue-500', patterns: ['termin', 'buchung', 'reservierung', 'vereinbarung'] },
            'absage': { label: 'Absage', class: 'keyword-absage', colorClass: 'bg-red-500', patterns: ['absag', 'stornierung', 'stornieren', 'absagen', 'canceln'] },
            'problem': { label: 'Problem', class: 'keyword-problem', colorClass: 'bg-yellow-500', patterns: ['problem', 'fehler', 'issue', 'schwierigkeiten', 'funktioniert nicht'] },
            'beschwerde': { label: 'Beschwerde', class: 'keyword-beschwerde', colorClass: 'bg-pink-500', patterns: ['beschwerd', 'unzufrieden', 'reklamation', 'ärger', 'frustriert'] },
            'bestaetigung': { label: 'Bestätigung', class: 'keyword-bestaetigung', colorClass: 'bg-green-500', patterns: ['bestätigung', 'bestätigt', 'konfirmation', 'gebucht', 'erfolgreich'] }
        };

        var keywordCounts = {};
        var activeKeywordFilter = null;

        function detectKeywords() {
            if (!messagesContainer) return;

            // Reset counts
            Object.keys(keywords).forEach(function(k) { keywordCounts[k] = 0; });

            messagesContainer.querySelectorAll('.msg').forEach(function(el) {
                var text = (el.dataset.text || '').toLowerCase();
                var msgKeywords = [];

                Object.keys(keywords).forEach(function(key) {
                    var kw = keywords[key];
                    var found = kw.patterns.some(function(p) {
                        return text.indexOf(p.toLowerCase()) > -1;
                    });
                    if (found) {
                        keywordCounts[key]++;
                        msgKeywords.push(key);
                    }
                });

                // Store detected keywords on element
                el.dataset.keywords = msgKeywords.join(',');

                // Add badges to message header
                var header = el.querySelector('.flex.items-center');
                if (!header) return;

                // Remove existing badges
                header.querySelectorAll('.keyword-badge').forEach(function(b) { b.remove(); });

                // Add new badges (safe DOM creation)
                msgKeywords.forEach(function(key) {
                    var kw = keywords[key];
                    var badge = document.createElement('span');
                    badge.className = 'keyword-badge ' + kw.class;
                    badge.textContent = kw.label;
                    header.appendChild(badge);
                });
            });

            // Update summary bar
            updateKeywordSummary();
        }

        function updateKeywordSummary() {
            if (!keywordSummary) return;

            var hasKeywords = Object.values(keywordCounts).some(function(c) { return c > 0; });

            if (!hasKeywords) {
                keywordSummary.classList.add('hidden');
                return;
            }

            keywordSummary.classList.remove('hidden');
            // Clear existing content safely
            while (keywordSummary.firstChild) {
                keywordSummary.removeChild(keywordSummary.firstChild);
            }

            // Add label
            var label = document.createElement('span');
            label.className = 'text-xs font-medium text-gray-500 dark:text-gray-400 mr-2';
            label.textContent = 'Keywords:';
            keywordSummary.appendChild(label);

            Object.keys(keywords).forEach(function(key) {
                if (keywordCounts[key] === 0) return;

                var kw = keywords[key];
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'keyword-summary-item' + (activeKeywordFilter === key ? ' active' : '');
                item.dataset.keywordFilter = key;

                // Build content safely using DOM methods
                var dot = document.createElement('span');
                dot.className = 'inline-block w-2 h-2 rounded-full ' + kw.colorClass;
                item.appendChild(dot);

                var labelText = document.createTextNode(' ' + kw.label + ' ');
                item.appendChild(labelText);

                var countSpan = document.createElement('span');
                countSpan.className = 'font-mono';
                countSpan.textContent = '(' + keywordCounts[key] + ')';
                item.appendChild(countSpan);

                item.addEventListener('click', function() {
                    if (activeKeywordFilter === key) {
                        activeKeywordFilter = null;
                        this.classList.remove('active');
                    } else {
                        keywordSummary.querySelectorAll('.keyword-summary-item').forEach(function(i) {
                            i.classList.remove('active');
                        });
                        activeKeywordFilter = key;
                        this.classList.add('active');
                    }
                    applyFilters();
                });

                keywordSummary.appendChild(item);
            });
        }

        // Extend applyFilters to include keyword filtering
        var originalApplyFilters = applyFilters;
        applyFilters = function() {
            if (!messagesContainer) return;
            var searchVal = searchInput ? searchInput.value.trim().toLowerCase() : '';

            messagesContainer.querySelectorAll('.msg').forEach(function(el) {
                var txt = el.querySelector('.msg-text');
                var original = el.dataset.text;
                var msgSpeaker = el.classList.contains('flex-row-reverse') ? 'customer' : (el.classList.contains('justify-center') ? 'system' : 'agent');
                var msgKeywords = (el.dataset.keywords || '').split(',').filter(Boolean);

                // Check speaker filter
                var speakerMatch = currentSpeakerFilter === 'all' || currentSpeakerFilter === msgSpeaker;

                // Check search filter
                var searchMatch = !searchVal || original.toLowerCase().indexOf(searchVal) > -1;

                // Check keyword filter
                var keywordMatch = !activeKeywordFilter || msgKeywords.indexOf(activeKeywordFilter) > -1;

                if (speakerMatch && searchMatch && keywordMatch) {
                    el.style.display = '';
                    // Apply search highlighting if needed
                    if (searchVal && original.toLowerCase().indexOf(searchVal) > -1) {
                        var parts = original.split(new RegExp('(' + searchVal.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'));
                        txt.textContent = '';
                        parts.forEach(function(part) {
                            if (part.toLowerCase() === searchVal) {
                                var mark = document.createElement('mark');
                                mark.className = 'bg-yellow-300 dark:bg-yellow-500 px-0.5 rounded';
                                mark.textContent = part;
                                txt.appendChild(mark);
                            } else {
                                txt.appendChild(document.createTextNode(part));
                            }
                        });
                    } else {
                        txt.textContent = original;
                    }
                } else {
                    el.style.display = 'none';
                }
            });
        };

        // Run keyword detection on init
        setTimeout(detectKeywords, 100);

        // Audio-Transcript Sync
        function updateActiveMessage(currentTime) {
            if (!messagesContainer) return;
            var messages = messagesContainer.querySelectorAll('.msg[data-start]');
            var newActiveIdx = -1;

            messages.forEach(function(el, idx) {
                var start = parseFloat(el.dataset.start);
                var end = parseFloat(el.dataset.end);
                if (!isNaN(start) && currentTime >= start && (isNaN(end) || currentTime <= end + 0.5)) {
                    newActiveIdx = idx;
                }
            });

            if (newActiveIdx !== activeIdx) {
                messages.forEach(function(el) {
                    el.classList.remove('ring-2', 'ring-primary-500', 'ring-offset-2', 'bg-primary-50', 'dark:bg-primary-900/20', 'scale-[1.02]', 'active');
                });

                if (newActiveIdx >= 0) {
                    var activeEl = messages[newActiveIdx];
                    activeEl.classList.add('ring-2', 'ring-primary-500', 'ring-offset-2', 'bg-primary-50', 'dark:bg-primary-900/20', 'scale-[1.02]', 'active');

                    if (autoScroll) {
                        var containerRect = messagesContainer.getBoundingClientRect();
                        var messageRect = activeEl.getBoundingClientRect();
                        var isAbove = messageRect.top < containerRect.top;
                        var isBelow = messageRect.bottom > containerRect.bottom;

                        if (isAbove || isBelow) {
                            var targetOffset = messageRect.top - containerRect.top + messagesContainer.scrollTop;
                            var centeredPosition = targetOffset - (containerRect.height * 0.33);
                            messagesContainer.scrollTo({
                                top: Math.max(0, centeredPosition),
                                behavior: 'smooth'
                            });
                        }
                    }
                }

                activeIdx = newActiveIdx;
            }
        }

        // Click-to-Seek on Messages (Phase 11: Added visual feedback)
        if (messagesContainer) {
            messagesContainer.addEventListener('click', function(e) {
                var msg = e.target.closest('.msg[data-start]');
                if (!msg) return;
                var start = parseFloat(msg.dataset.start);
                if (!isNaN(start)) {
                    // Phase 11: Visual feedback for seek action
                    msg.classList.add('animate-pulse', 'bg-primary-100', 'dark:bg-primary-800/40');
                    setTimeout(function() {
                        msg.classList.remove('animate-pulse', 'bg-primary-100', 'dark:bg-primary-800/40');
                    }, 500);

                    wavesurfer.setTime(start);
                    wavesurfer.play();
                    autoScroll = false;
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(function() {
                        autoScroll = true;
                    }, 3000);

                    // Announce for screen readers
                    announce('Springe zu ' + formatTime(start));
                }
            });
        }

        // Global Keyboard Shortcuts
        function handleGlobalKeydown(e) {
            if (!container.offsetParent) return;
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                if (e.key === 'Escape') e.target.blur();
                return;
            }

            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    wavesurfer.playPause();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    wavesurfer.skip(-10);
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    wavesurfer.skip(10);
                    break;
                case 'm':
                case 'M':
                    var isMuted = wavesurfer.getVolume() === 0;
                    wavesurfer.setVolume(isMuted ? 1 : 0);
                    if (volumeInput) volumeInput.value = isMuted ? 1 : 0;
                    updateVolumeIcon(isMuted ? 1 : 0);
                    break;
                case '/':
                    e.preventDefault();
                    if (searchInput) searchInput.focus();
                    break;
            }
        }

        document.addEventListener('keydown', handleGlobalKeydown);

        // Cleanup on component removal
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.removedNodes.forEach(function(node) {
                    if (node === container || (node.contains && node.contains(container))) {
                        wavesurfer.destroy();
                        document.removeEventListener('keydown', handleGlobalKeydown);
                        observer.disconnect();
                        console.log('[Wavesurfer] Cleanup completed');
                    }
                });
            });
        });

        if (container.parentNode) {
            observer.observe(container.parentNode, { childList: true, subtree: true });
        }

        // Expose for external access
        window['_wavesurfer_' + id] = wavesurfer;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

</div>{{-- End: recording-transcript-wrapper --}}
