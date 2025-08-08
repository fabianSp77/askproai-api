@php
    $state = $getState();
    $callId = $state['call_id'] ?? null;
    $call = \App\Models\Call::find($callId);
@endphp

@if($call)
    @livewire('call-notes-component', ['call' => $call])
@else
    <div class="text-center py-8">
        <p class="text-sm text-gray-500 dark:text-gray-400">Anruf nicht gefunden</p>
    </div>
@endif