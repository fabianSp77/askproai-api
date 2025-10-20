@php
    $record = $getRecord();

    // Company/Branch name and link
    $branchLink = null;
    $companyLink = null;

    if ($record->branch_id && $record->branch) {
        $companyName = $record->branch->name ?? 'Filiale';
        try {
            $branchLink = route('filament.admin.resources.branches.view', ['record' => $record->branch_id]);
        } catch (\Exception $e) {
            $branchLink = null;
        }
    } elseif ($record->company_id && $record->company) {
        $companyName = $record->company->name ?? 'Unternehmen';
        try {
            $companyLink = route('filament.admin.resources.companies.view', ['record' => $record->company_id]);
        } catch (\Exception $e) {
            $companyLink = null;
        }
    } else {
        $companyName = 'Unternehmen';
    }

    // Phone number from phoneNumber relationship
    $phoneNumber = null;
    if ($record->phoneNumber && $record->phoneNumber->number) {
        $phoneNumber = $record->phoneNumber->number;
    }

    // Tooltip
    $tooltipText = "🏢 " . $companyName;
    if ($phoneNumber) {
        $tooltipText .= "\n📞 " . $phoneNumber;
    }
@endphp

<div class="space-y-1" title="{{ $tooltipText }}">
    <!-- Zeile 1: Company/Branch Name (klickbar wenn Link vorhanden) -->
    <div class="text-sm font-medium">
        @if($branchLink)
            <a href="{{ $branchLink }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                🏢 {{ $companyName }}
            </a>
        @elseif($companyLink)
            <a href="{{ $companyLink }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                🏢 {{ $companyName }}
            </a>
        @else
            🏢 {{ $companyName }}
        @endif
    </div>

    <!-- Zeile 2: Phone Number (Kopier-funktionalität) -->
    @if($phoneNumber)
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-600">
                📞 {{ $phoneNumber }}
            </span>
            <button
                type="button"
                onclick="navigator.clipboard.writeText('{{ $phoneNumber }}').then(() => alert('📋 Nummer kopiert!'))"
                class="text-xs text-blue-600 hover:text-blue-800 cursor-pointer font-medium"
                title="Nummer kopieren"
            >
                📋
            </button>
        </div>
    @endif
</div>
