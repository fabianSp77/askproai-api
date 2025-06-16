<div wire:poll.15s class="ml-2">
    @if($ok)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            Queue OK
        </span>
    @else
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
            Queue FAIL
        </span>
    @endif
</div>
