<div class="w-full {{ $bg }} rounded-lg p-4 border-2 {{ $border }} mb-6">
    <div class="flex items-center gap-3">
        <div class="{{ $textColor }}">
            {!! $icon !!}
        </div>
        <div class="flex-1">
            <div class="font-semibold text-lg {{ $textColor }}">{{ $text }}</div>
            @if($subtext)
                <div class="text-sm {{ $textColor }} opacity-80">{{ $subtext }}</div>
            @endif
        </div>
    </div>
</div>
