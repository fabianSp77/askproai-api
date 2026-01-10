{{-- Preset Selector Grid with Click-to-Select --}}
<div x-data="{ selectedPresetId: @entangle('data.selected_preset_id') }">
    @if($presets->isEmpty())
        <div class="text-center py-12">
            <div class="text-gray-400 dark:text-gray-500">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-sm font-medium">Keine Vorlagen verfügbar</p>
                <p class="mt-1 text-xs">Bitte führen Sie den EmailTemplatePresetSeeder aus.</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($presets as $preset)
                <div
                    @click="selectedPresetId = {{ $preset->id }}"
                    :class="{ 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20': selectedPresetId == {{ $preset->id }} }"
                    class="relative cursor-pointer rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200 group"
                    role="button"
                    tabindex="0"
                    @keydown.enter="selectedPresetId = {{ $preset->id }}"
                    @keydown.space.prevent="selectedPresetId = {{ $preset->id }}"
                    aria-label="Vorlage {{ $preset->name }} auswählen"
                >
                    {{-- Selection indicator --}}
                    <div
                        x-show="selectedPresetId == {{ $preset->id }}"
                        x-cloak
                        class="absolute top-2 right-2"
                    >
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    {{-- Preset content --}}
                    <div class="pr-8">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                            {{ $preset->name }}
                        </h3>

                        @if($preset->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ $preset->description }}
                            </p>
                        @endif

                        @if($preset->variables_hint)
                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Verfügbare Variablen:
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 line-clamp-2">
                                    {{ Str::limit($preset->variables_hint, 150) }}
                                </p>
                            </div>
                        @endif

                        {{-- Action buttons --}}
                        <div class="mt-3 flex items-center gap-2">
                            {{-- Key badge --}}
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ $preset->key }}
                            </span>

                            {{-- Preview button --}}
                            <button
                                type="button"
                                @click.stop="$dispatch('open-preset-preview', { presetId: {{ $preset->id }} })"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                aria-label="Vorschau von {{ $preset->name }} anzeigen"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Vorschau
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Hidden input to store selection --}}
        <input type="hidden" name="selected_preset_id" x-model="selectedPresetId">

        {{-- Helper text --}}
        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            <p>
                <svg class="inline h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Klicken Sie auf eine Vorlage, um sie auszuwählen. Das neue Template wird als Entwurf erstellt.
            </p>
        </div>

        {{-- Preview Modal --}}
        <div
            x-data="{
                showPreview: false,
                previewPresetId: null,
                previewData: null
            }"
            @open-preset-preview.window="
                previewPresetId = $event.detail.presetId;
                showPreview = true;
            "
        >
            <div
                x-show="showPreview"
                x-cloak
                class="fixed inset-0 z-[100] overflow-y-auto"
                aria-labelledby="modal-title"
                role="dialog"
                aria-modal="true"
                @click.self="showPreview = false"
                @keydown.escape.window="showPreview = false"
            >
                <div class="flex min-h-screen items-center justify-center p-4">
                    {{-- Backdrop --}}
                    <div
                        x-show="showPreview"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity"
                        aria-hidden="true"
                    ></div>

                    {{-- Modal content --}}
                    <div
                        x-show="showPreview"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 w-full sm:max-w-4xl"
                    >
                        {{-- Modal header --}}
                        <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="modal-title">
                                    Template-Vorschau
                                </h3>
                                <button
                                    type="button"
                                    @click="showPreview = false"
                                    class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    aria-label="Schließen"
                                >
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Modal body with preview content --}}
                        <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                            @foreach($presets as $preset)
                                <div x-show="previewPresetId == {{ $preset->id }}">
                                    {{-- Preset name --}}
                                    <div class="mb-4">
                                        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $preset->name }}
                                        </h4>
                                        @if($preset->description)
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $preset->description }}
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Subject preview --}}
                                    <div class="mb-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Betreff</span>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 px-4 py-3">
                                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                                @php
                                                    // Simple variable replacement for preview
                                                    $sampleSubject = $preset->subject;
                                                    $sampleSubject = str_replace('{{case_id}}', '#12345', $sampleSubject);
                                                    $sampleSubject = str_replace('{{subject}}', 'Drucker in Raum 204 zeigt Papierstau-Fehler', $sampleSubject);
                                                    $sampleSubject = str_replace('{{priority}}', 'Hoch', $sampleSubject);
                                                    $sampleSubject = str_replace('{{status}}', 'Offen', $sampleSubject);
                                                @endphp
                                                {{ $sampleSubject }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Body preview --}}
                                    <div class="mb-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">E-Mail Body</span>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 max-h-96 overflow-y-auto">
                                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                                @php
                                                    // Get sample data
                                                    $sampleCase = \App\Services\ServiceGateway\SampleServiceCaseFactory::create();
                                                    $dataProvider = new \App\Services\ServiceGateway\EmailTemplateDataProvider($sampleCase);
                                                    $variables = $dataProvider->getVariables();

                                                    // Render template with variables
                                                    $renderedBody = $preset->body_html;
                                                    foreach ($variables as $key => $value) {
                                                        $renderedBody = str_replace('{{' . $key . '}}', $value, $renderedBody);
                                                    }
                                                @endphp
                                                {!! $renderedBody !!}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Variables hint --}}
                                    @if($preset->variables_hint)
                                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center gap-2 mb-2">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                                </svg>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Verwendete Variablen</span>
                                            </div>
                                            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 px-4 py-3">
                                                <p class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $preset->variables_hint }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Modal footer --}}
                        <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    @click="showPreview = false"
                                    class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                >
                                    Schließen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
