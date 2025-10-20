@php
    $record = $getRecord();
    $nonNamePhrases = ['mir nicht', 'guten tag', 'guten morgen', 'hallo', 'ja', 'nein', 'gleich fertig', 'ja bitte', 'danke'];
    $customerNameLower = $record->customer_name ? strtolower(trim($record->customer_name)) : '';
    $isTranscriptFragment = in_array($customerNameLower, $nonNamePhrases);

    // Determine display name and link
    $displayName = null;
    $customerLink = null;
    $isAnonymous = false;

    if ($record->from_number === 'anonymous' && (!$record->customer_name || trim($record->customer_name) === '' || $isTranscriptFragment)) {
        $displayName = "Anonymer Anrufer";
        $isAnonymous = true;
    } elseif ($record->customer_id && $record->customer) {
        $displayName = $record->customer->name;
        $customerLink = route('filament.admin.resources.customers.view', $record->customer_id);
    } elseif ($record->customer_name) {
        $displayName = $record->customer_name;
        $isAnonymous = false;
    } else {
        $displayName = "Anonymer Anrufer";
        $isAnonymous = true;
    }

    // Phone number
    $phoneNumber = $record->from_number ?? $record->to_number;
    if ($phoneNumber === 'anonymous' || !$phoneNumber) {
        $phoneDisplay = "Rufnummer nicht übertragen";
        $showCopy = false;
    } else {
        $phoneDisplay = $phoneNumber;
        $showCopy = true;
    }

    // Email from customer
    $customerEmail = $record->customer?->email ?? null;

    // Tooltip
    $tooltipText = $displayName;
    if ($showCopy) {
        $tooltipText .= "\n" . $phoneNumber;
    } else {
        $tooltipText .= "\nRufnummer nicht übertragen";
    }
    if ($customerEmail) {
        $tooltipText .= "\n" . $customerEmail;
    }
@endphp

<div class="space-y-1" title="{{ $tooltipText }}">
    <!-- Zeile 1: Name (klickbar wenn Customer) -->
    <div class="text-sm font-medium text-gray-800">
        @if($customerLink)
            <a href="{{ $customerLink }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                {{ $displayName }}
            </a>
        @else
            {{ $displayName }}
        @endif
    </div>

    <!-- Zeile 2: Telefonnummer (NICHT klickbar) -->
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-600 font-mono">
            {{ $phoneDisplay }}
        </span>

        @if($showCopy)
            <button
                type="button"
                onclick="navigator.clipboard.writeText('{{ $phoneNumber }}').then(() => alert('Kopiert!'))"
                class="text-xs text-gray-500 hover:text-blue-600 cursor-pointer transition-colors"
                title="In Zwischenablage kopieren"
            >
                copy
            </button>
        @endif
    </div>

    <!-- Zeile 3: Email (falls vorhanden) -->
    @if($customerEmail)
        <div class="text-xs text-gray-600 font-mono">
            {{ $customerEmail }}
        </div>
    @endif
</div>
