@props([
    'title' => '',
    'value' => '',
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'blue',
    'trend' => null,
    'trendDirection' => 'up',
    'loading' => false,
])

@php
    $iconColors = [
        'blue' => 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300',
        'green' => 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300',
        'purple' => 'bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300',
        'orange' => 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300',
        'red' => 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300',
        'yellow' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300',
        'gray' => 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-300',
    ];
    
    $trendColors = [
        'up' => 'text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-300',
        'down' => 'text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300',
        'neutral' => 'text-gray-600 bg-gray-100 dark:bg-gray-900 dark:text-gray-300',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700 transition-all hover:shadow-lg']) }}>
    @if($loading)
        {{-- Loading State --}}
        <div class="animate-pulse">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                <div class="w-16 h-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-2"></div>
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-32 mb-2"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
        </div>
    @else
        {{-- Content --}}
        <div class="flex items-center justify-between mb-4">
            @if($icon)
                <div class="w-12 h-12 {{ $iconColors[$iconColor] ?? $iconColors['blue'] }} rounded-lg flex items-center justify-center">
                    @if(str_starts_with($icon, '<svg'))
                        {!! $icon !!}
                    @else
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            @switch($icon)
                                @case('phone')
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                    @break
                                @case('users')
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    @break
                                @case('calendar')
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                    @break
                                @case('chart')
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                    @break
                                @case('clock')
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    @break
                                @case('check')
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    @break
                                @case('star')
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    @break
                                @default
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            @endswitch
                        </svg>
                    @endif
                </div>
            @endif
            
            @if($trend)
                <span class="text-xs font-medium {{ $trendColors[$trendDirection] ?? $trendColors['neutral'] }} px-2 py-1 rounded flex items-center">
                    @if($trendDirection === 'up')
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    @elseif($trendDirection === 'down')
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                    {{ $trend }}
                </span>
            @endif
        </div>
        
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $title }}</h3>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $value }}</p>
        
        @if($subtitle)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $subtitle }}</p>
        @endif
    @endif
</div>