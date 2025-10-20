@php
    $record = $getRecord();

    // Company and Branch names - SHOW BOTH
    $branchLink = null;
    $companyLink = null;
    $branchName = null;
    $companyName = null;

    // Get company name
    if ($record->company_id && $record->company) {
        $companyName = $record->company->name ?? 'Unternehmen';
        try {
            $companyLink = route('filament.admin.resources.companies.view', ['record' => $record->company_id]);
        } catch (\Exception $e) {
            $companyLink = null;
        }
    }

    // Get branch name
    if ($record->branch_id && $record->branch) {
        $branchName = $record->branch->name ?? 'Filiale';
        try {
            $branchLink = route('filament.admin.resources.branches.view', ['record' => $record->branch_id]);
        } catch (\Exception $e) {
            $branchLink = null;
        }
    }

    // Phone number from phoneNumber relationship
    $phoneNumber = null;
    if ($record->phoneNumber && $record->phoneNumber->number) {
        $phoneNumber = $record->phoneNumber->number;
    }

    // Tooltip - Show hierarchy
    $tooltipText = "🏢 Unternehmen: " . ($companyName ?? 'Unbekannt');
    if ($branchName) {
        $tooltipText .= "\n🏪 Filiale: " . $branchName;
    }
    if ($phoneNumber) {
        $tooltipText .= "\n📞 " . $phoneNumber;
    }
@endphp

<div class="space-y-1" title="{{ $tooltipText }}">
    <!-- Zeile 1: Unternehmen Name (klickbar wenn Link vorhanden) -->
    <div class="text-xs font-medium text-gray-800">
        @if($companyLink && $companyName)
            <a href="{{ $companyLink }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                {{ $companyName }}
            </a>
        @elseif($companyName)
            {{ $companyName }}
        @else
            Unbekannt
        @endif
    </div>

    <!-- Zeile 2: Filiale Name (klickbar wenn Link vorhanden) - nur wenn vorhanden -->
    @if($branchName)
        <div class="text-xs text-gray-600">
            @if($branchLink)
                <a href="{{ $branchLink }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                    {{ $branchName }}
                </a>
            @else
                {{ $branchName }}
            @endif
        </div>
    @endif

    <!-- Zeile 3: Phone Number (Kopier-funktionalität) -->
    @if($phoneNumber)
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-600 font-mono">
                {{ $phoneNumber }}
            </span>
            <button
                type="button"
                onclick="navigator.clipboard.writeText('{{ $phoneNumber }}').then(() => alert('Kopiert!'))"
                class="text-xs text-gray-500 hover:text-blue-600 cursor-pointer transition-colors"
                title="In Zwischenablage kopieren"
            >
                copy
            </button>
        </div>
    @endif
</div>
