{{-- Skeleton Loader for Call Header --}}
<div class="animate-pulse">
    {{-- Hero Section Skeleton --}}
    <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-6">
        {{-- Customer Info --}}
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-gray-300 dark:bg-gray-700 rounded-full"></div>
                <div>
                    <div class="h-6 w-48 bg-gray-300 dark:bg-gray-700 rounded mb-2"></div>
                    <div class="h-4 w-32 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </div>
            </div>
            <div class="h-8 w-24 bg-gray-300 dark:bg-gray-700 rounded-lg"></div>
        </div>
        
        {{-- Summary --}}
        <div class="mt-4 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-full"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4"></div>
        </div>
        
        {{-- Metrics Grid --}}
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                        <div class="flex-1">
                            <div class="h-3 w-16 bg-gray-200 dark:bg-gray-600 rounded mb-1"></div>
                            <div class="h-4 w-24 bg-gray-300 dark:bg-gray-700 rounded"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
    
    {{-- Financial Metrics Skeleton --}}
    <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl p-6">
        <div class="h-4 w-24 bg-gray-300 dark:bg-gray-700 rounded mb-4"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @for($i = 0; $i < 3; $i++)
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                    <div class="h-3 w-16 bg-gray-200 dark:bg-gray-600 rounded mb-2"></div>
                    <div class="h-8 w-24 bg-gray-300 dark:bg-gray-800 rounded mb-2"></div>
                    <div class="h-2 w-full bg-gray-200 dark:bg-gray-600 rounded"></div>
                </div>
            @endfor
        </div>
    </div>
</div>