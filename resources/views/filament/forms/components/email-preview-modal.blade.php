{{-- Email Preview Modal for ServiceOutputConfiguration --}}
@php
    // Ensure we have proper defaults
    $templateType = $templateType ?? 'standard';
    $includeTranscript = $includeTranscript ?? true;
    $includeSummary = $includeSummary ?? true;
    $audioOption = $audioOption ?? 'none';
    $showAdminLink = $showAdminLink ?? false;

    // Template labels
    $templateLabels = [
        'standard' => 'Standard',
        'technical' => 'Technisch',
        'admin' => 'IT-Support',
        'custom' => 'Custom',
    ];

    $audioLabels = [
        'none' => 'Nicht einbinden',
        'link' => 'Download-Link',
        'attachment' => 'Als Anhang',
    ];
@endphp

<div
    x-data="{
        loading: true,
        error: null,
        subject: '',
        htmlContent: '',
        htmlSize: 0,

        templateType: '{{ $templateType }}',
        includeTranscript: {{ $includeTranscript ? 'true' : 'false' }},
        includeSummary: {{ $includeSummary ? 'true' : 'false' }},
        audioOption: '{{ $audioOption }}',
        showAdminLink: {{ $showAdminLink ? 'true' : 'false' }},

        async loadPreview() {
            this.loading = true;
            this.error = null;

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

                // Inject HTML into iframe
                if (this.$refs.previewFrame) {
                    this.$refs.previewFrame.srcdoc = this.htmlContent;
                }
            } catch (err) {
                console.error('[EmailPreview] Error:', err);
                this.error = err.message || 'Vorschau konnte nicht geladen werden';
            } finally {
                this.loading = false;
            }
        },

        getTemplateLabel() {
            const labels = {
                'standard': 'Standard',
                'technical': 'Technisch',
                'admin': 'IT-Support',
                'custom': 'Custom',
            };
            return labels[this.templateType] || this.templateType;
        },

        getAudioLabel() {
            const labels = {
                'none': 'Nicht einbinden',
                'link': 'Download-Link',
                'attachment': 'Als Anhang',
            };
            return labels[this.audioOption] || this.audioOption;
        }
    }"
    x-init="loadPreview()"
    class="grid grid-cols-1 lg:grid-cols-3 gap-6"
>
    {{-- Left Column: Current Settings --}}
    <div class="col-span-1 bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            Aktuelle Einstellungen
        </h4>

        <dl class="space-y-4 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Template</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $templateLabels[$templateType] ?? $templateType }}</dd>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Transkript</dt>
                <dd>
                    @if($includeTranscript)
                        <span class="text-success-600">Ja</span>
                    @else
                        <span class="text-gray-400">Nein</span>
                    @endif
                </dd>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Zusammenfassung</dt>
                <dd>
                    @if($includeSummary)
                        <span class="text-success-600">Ja</span>
                    @else
                        <span class="text-gray-400">Nein</span>
                    @endif
                </dd>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Audio</dt>
                <dd class="font-medium">{{ $audioLabels[$audioOption] ?? $audioOption }}</dd>
            </div>
            <div class="flex justify-between items-center py-2">
                <dt class="text-gray-500 dark:text-gray-400">Admin-Link</dt>
                <dd>
                    @if($showAdminLink)
                        <span class="text-success-600">Ja</span>
                    @else
                        <span class="text-gray-400">Nein</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div class="text-xs text-blue-700 dark:text-blue-300">
                    <p class="font-medium mb-1">Beispieldaten</p>
                    <p>Diese Vorschau verwendet Testdaten. Die echte E-Mail enthält die tatsächlichen Ticket-Informationen.</p>
                </div>
            </div>
        </div>

        {{-- Refresh Button --}}
        <button
            type="button"
            @click="loadPreview()"
            class="mt-4 w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 dark:text-primary-400 rounded-lg transition-colors"
            :disabled="loading"
        >
            <svg class="w-4 h-4" :class="loading && 'animate-spin'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            <span x-text="loading ? 'Laden...' : 'Vorschau aktualisieren'"></span>
        </button>
    </div>

    {{-- Right Column: Email Preview --}}
    <div class="col-span-1 lg:col-span-2">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            {{-- Email Header --}}
            <div class="bg-gray-100 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Betreff:</div>
                        <div class="font-medium text-gray-900 dark:text-white truncate" x-text="subject || 'Wird geladen...'"></div>
                    </div>
                </div>
            </div>

            {{-- Email Body (iFrame) --}}
            <div class="relative bg-white" style="height: 500px;">
                {{-- Loading Spinner --}}
                <div
                    x-show="loading"
                    x-transition
                    class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800 z-10"
                >
                    <svg class="animate-spin h-10 w-10 text-primary-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-gray-500 dark:text-gray-400">E-Mail wird gerendert...</span>
                </div>

                {{-- Error State --}}
                <div
                    x-show="error"
                    x-cloak
                    class="absolute inset-0 flex flex-col items-center justify-center bg-red-50 dark:bg-red-900/20 z-10"
                >
                    <svg class="w-12 h-12 text-red-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <p class="text-sm font-medium text-red-600 dark:text-red-400 mb-1">Fehler beim Laden</p>
                    <p class="text-xs text-red-500 dark:text-red-300 max-w-md text-center px-4" x-text="error"></p>
                    <button
                        type="button"
                        @click="loadPreview()"
                        class="mt-3 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 rounded-md transition-colors"
                    >
                        Erneut versuchen
                    </button>
                </div>

                {{-- Preview iFrame --}}
                <iframe
                    x-ref="previewFrame"
                    x-show="!loading && !error"
                    class="w-full h-full border-0"
                    sandbox="allow-same-origin"
                    title="E-Mail Vorschau"
                ></iframe>
            </div>

            {{-- Footer with meta info --}}
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Template: <span class="font-medium" x-text="templateType"></span></span>
                    <span x-show="htmlSize > 0" x-text="'Größe: ' + Math.round(htmlSize / 1024) + ' KB'"></span>
                </div>
            </div>
        </div>
    </div>
</div>
