{{-- Notes wrapper receives ServiceCase directly to avoid N+1 and maintain tenant scope --}}
@if($serviceCase)
    <div class="notes-wrapper">
        @livewire(\App\Livewire\ServiceCase\NotesSection::class, ['serviceCase' => $serviceCase], key('notes-' . $serviceCase->id))
    </div>
@else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400" role="status">
        <x-heroicon-o-exclamation-triangle class="w-8 h-8 mx-auto mb-2 opacity-50" aria-hidden="true" />
        <p class="text-sm">ServiceCase nicht gefunden</p>
    </div>
@endif
