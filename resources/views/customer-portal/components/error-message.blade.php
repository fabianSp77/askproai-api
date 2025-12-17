@props(['message' => '', 'type' => 'error', 'showIcon' => true])

<div class="rounded-md p-4 {{ $type === 'error' ? 'bg-red-50 border border-red-200' : ($type === 'warning' ? 'bg-yellow-50 border border-yellow-200' : 'bg-blue-50 border border-blue-200') }}">
    <div class="flex">
        @if($showIcon)
        <div class="flex-shrink-0">
            @if($type === 'error')
                <i class="fas fa-exclamation-circle text-red-400"></i>
            @elseif($type === 'warning')
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            @else
                <i class="fas fa-info-circle text-blue-400"></i>
            @endif
        </div>
        @endif
        <div class="{{ $showIcon ? 'ml-3' : '' }} flex-1">
            <p class="text-sm font-medium {{ $type === 'error' ? 'text-red-800' : ($type === 'warning' ? 'text-yellow-800' : 'text-blue-800') }}">
                {{ $message }}
            </p>
            @if(isset($slot) && $slot != '')
                <div class="mt-2 text-sm {{ $type === 'error' ? 'text-red-700' : ($type === 'warning' ? 'text-yellow-700' : 'text-blue-700') }}">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</div>
