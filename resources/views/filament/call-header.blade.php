<div class="bg-white dark:bg-gray-800 rounded-lg p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        {{-- Main Info --}}
        <div class="flex-1">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-2 truncate">
                {{ $customerName }}
            </h1>

            <div class="flex flex-wrap items-center gap-3 text-sm">
                {{-- Direction --}}
                <div class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                    @if($direction === 'Eingehend')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    @endif
                    <span>{{ $direction }}</span>
                    <span class="text-gray-400">•</span>
                    <span class="font-mono">{{ $phoneNumber }}</span>
                </div>

                {{-- Date & Time --}}
                <div class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ $date }}</span>
                </div>

                {{-- Duration --}}
                <div class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $duration }}</span>
                </div>

                {{-- Status Badge --}}
                <span @class([
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' => $statusColor === 'success',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' => $statusColor === 'warning',
                    'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' => $statusColor === 'danger',
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $statusColor === 'gray',
                ])>
                    {{ $statusText }}
                </span>
            </div>
        </div>

        {{-- ID Button --}}
        @if($id)
        <div class="flex-shrink-0">
            <button type="button"
                    onclick="copyToClipboard('{{ $id }}')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium
                           text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700
                           border border-gray-300 dark:border-gray-600 rounded-md
                           hover:bg-gray-50 dark:hover:bg-gray-600
                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                           transition-all duration-150"
                    data-id="{{ $id }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span class="button-text">ID: {{ $id }}</span>
            </button>
        </div>
        @endif
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const button = event.currentTarget;
        const originalText = button.querySelector('.button-text').innerText;
        button.querySelector('.button-text').innerText = 'Kopiert!';
        button.classList.add('bg-green-50', 'dark:bg-green-900', 'border-green-500');

        setTimeout(() => {
            button.querySelector('.button-text').innerText = originalText;
            button.classList.remove('bg-green-50', 'dark:bg-green-900', 'border-green-500');
        }, 2000);
    });
}
</script>