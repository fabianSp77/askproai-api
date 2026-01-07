{{-- Email Preview Modal for ServiceOutputConfiguration --}}
@php
    // Ensure we have proper defaults
    $templateType = $templateType ?? 'standard';
    $includeTranscript = $includeTranscript ?? true;
    $includeSummary = $includeSummary ?? true;
    $audioOption = $audioOption ?? 'none';
    $showAdminLink = $showAdminLink ?? false;

    // Template configuration with icons and colors
    $templateConfig = [
        'standard' => ['label' => 'Standard', 'icon' => '📋', 'color' => 'gray', 'desc' => 'Für Team-Benachrichtigungen'],
        'technical' => ['label' => 'Technisch', 'icon' => '🔬', 'color' => 'blue', 'desc' => 'Mit JSON-Anhang'],
        'admin' => ['label' => 'IT-Support', 'icon' => '🛠️', 'color' => 'amber', 'desc' => 'Strukturierte Ticket-Info'],
        'custom' => ['label' => 'Custom', 'icon' => '⚙️', 'color' => 'purple', 'desc' => 'Eigenes Template'],
    ];

    $audioConfig = [
        'none' => ['label' => 'Nicht einbinden', 'icon' => '🚫'],
        'link' => ['label' => 'Download-Link', 'icon' => '🔗'],
        'attachment' => ['label' => 'Als Anhang', 'icon' => '📎'],
    ];

    $currentTemplate = $templateConfig[$templateType] ?? $templateConfig['standard'];
    $currentAudio = $audioConfig[$audioOption] ?? $audioConfig['none'];
@endphp

<div
    x-data="{
        loading: true,
        error: null,
        subject: '',
        htmlContent: '',
        htmlSize: 0,
        loadTime: 0,

        templateType: '{{ $templateType }}',
        includeTranscript: {{ $includeTranscript ? 'true' : 'false' }},
        includeSummary: {{ $includeSummary ? 'true' : 'false' }},
        audioOption: '{{ $audioOption }}',
        showAdminLink: {{ $showAdminLink ? 'true' : 'false' }},

        async loadPreview() {
            this.loading = true;
            this.error = null;
            const startTime = performance.now();

            try {
                const params = new URLSearchParams({
                    template_type: this.templateType,
                    include_transcript: this.includeTranscript ? '1' : '0',
                    include_summary: this.includeSummary ? '1' : '0',
                    email_audio_option: this.audioOption,
                    email_show_admin_link: this.showAdminLink ? '1' : '0',
                });

                const response = await fetch('/admin/api/output-config/preview?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || 'HTTP ' + response.status);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Unbekannter Fehler');
                }

                this.subject = data.subject || 'Service Case Benachrichtigung';
                this.htmlSize = data.html ? data.html.length : 0;
                this.htmlContent = data.html || '';
                this.loadTime = Math.round(performance.now() - startTime);

                // Use blob URL instead of srcdoc to avoid console warnings
                if (this.$refs.previewFrame) {
                    const blob = new Blob([this.htmlContent], { type: 'text/html' });
                    this.$refs.previewFrame.src = URL.createObjectURL(blob);
                }
            } catch (err) {
                console.error('[EmailPreview] Error:', err);
                this.error = err.message || 'Vorschau konnte nicht geladen werden';
            } finally {
                this.loading = false;
            }
        },

        openInNewTab() {
            if (this.htmlContent) {
                const blob = new Blob([this.htmlContent], { type: 'text/html' });
                window.open(URL.createObjectURL(blob), '_blank');
            }
        }
    }"
    x-init="loadPreview()"
    class="flex flex-col lg:flex-row gap-4 min-h-[600px]"
>
    {{-- Left Sidebar: Settings Summary --}}
    <div class="w-full lg:w-72 flex-shrink-0 space-y-4">
        {{-- Template Badge --}}
        <div class="bg-{{ $currentTemplate['color'] }}-50 dark:bg-{{ $currentTemplate['color'] }}-900/30 rounded-xl p-4 border border-{{ $currentTemplate['color'] }}-200 dark:border-{{ $currentTemplate['color'] }}-800">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">{{ $currentTemplate['icon'] }}</span>
                <div>
                    <div class="font-semibold text-{{ $currentTemplate['color'] }}-900 dark:text-{{ $currentTemplate['color'] }}-100">
                        {{ $currentTemplate['label'] }}
                    </div>
                    <div class="text-xs text-{{ $currentTemplate['color'] }}-600 dark:text-{{ $currentTemplate['color'] }}-400">
                        {{ $currentTemplate['desc'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Settings Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
            <div class="px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Transkript</span>
                @if($includeTranscript)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Aktiv
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        Aus
                    </span>
                @endif
            </div>
            <div class="px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Zusammenfassung</span>
                @if($includeSummary)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Aktiv
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        Aus
                    </span>
                @endif
            </div>
            <div class="px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Audio</span>
                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ $currentAudio['icon'] }} {{ $currentAudio['label'] }}
                </span>
            </div>
            <div class="px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Admin-Link</span>
                @if($showAdminLink)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Aktiv
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        Aus
                    </span>
                @endif
            </div>
        </div>

        {{-- Info Box --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div class="text-xs text-blue-700 dark:text-blue-300">
                    <p class="font-medium mb-1">Beispiel-Vorschau</p>
                    <p class="text-blue-600 dark:text-blue-400">Zeigt Testdaten. Echte E-Mails enthalten tatsächliche Ticket-Informationen.</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col gap-2">
            <button
                type="button"
                @click="loadPreview()"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 dark:text-primary-400 rounded-lg transition-colors border border-primary-200 dark:border-primary-800"
                :disabled="loading"
            >
                <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span x-text="loading ? 'Laden...' : 'Aktualisieren'"></span>
            </button>
            <button
                type="button"
                @click="openInNewTab()"
                x-show="!loading && !error && htmlContent"
                class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                In neuem Tab öffnen
            </button>
        </div>
    </div>

    {{-- Main Content: Email Preview --}}
    <div class="flex-1 min-w-0">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm h-full flex flex-col">
            {{-- Email Header --}}
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Betreff</div>
                            <div class="font-medium text-gray-900 dark:text-white truncate" x-text="subject || 'Wird geladen...'"></div>
                        </div>
                    </div>
                    {{-- Status Badge --}}
                    <div class="flex-shrink-0">
                        <template x-if="loading">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Lädt...
                            </span>
                        </template>
                        <template x-if="!loading && !error">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Bereit
                            </span>
                        </template>
                        <template x-if="!loading && error">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                Fehler
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Email Body (iFrame) --}}
            <div class="relative flex-1 bg-white min-h-[450px]">
                {{-- Loading Overlay --}}
                <div
                    x-show="loading"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50/90 dark:bg-gray-800/90 backdrop-blur-sm z-10"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-8 flex flex-col items-center">
                        <svg class="animate-spin h-12 w-12 text-primary-500 mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">E-Mail wird gerendert...</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Template: {{ $currentTemplate['label'] }}</p>
                    </div>
                </div>

                {{-- Error State --}}
                <div
                    x-show="error"
                    x-cloak
                    class="absolute inset-0 flex flex-col items-center justify-center bg-danger-50/90 dark:bg-danger-900/20 z-10"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-8 max-w-md text-center">
                        <div class="w-16 h-16 rounded-full bg-danger-100 dark:bg-danger-900/30 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-danger-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mb-2">Vorschau nicht verfügbar</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="error"></p>
                        <button
                            type="button"
                            @click="loadPreview()"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            Erneut versuchen
                        </button>
                    </div>
                </div>

                {{-- Preview iFrame --}}
                <iframe
                    x-ref="previewFrame"
                    x-show="!loading && !error"
                    class="w-full h-full border-0 min-h-[450px]"
                    title="E-Mail Vorschau"
                ></iframe>
            </div>

            {{-- Footer --}}
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2.5 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-4 text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span x-show="htmlSize > 0" x-text="Math.round(htmlSize / 1024) + ' KB'"></span>
                        </span>
                        <span class="flex items-center gap-1.5" x-show="loadTime > 0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span x-text="loadTime + ' ms'"></span>
                        </span>
                    </div>
                    <div class="text-gray-400 dark:text-gray-500">
                        {{ $currentTemplate['icon'] }} {{ $currentTemplate['label'] }} Template
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
