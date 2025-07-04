<div>
@php
    use Carbon\Carbon;
    use App\Models\BillingRate;
    use App\Services\ExchangeRateService;
    
    $record = $getRecord();
    
    // Extract all needed data
    $timestamp = $record->start_timestamp 
        ? Carbon::parse($record->start_timestamp)
        : $record->created_at;
    
    // Duration calculation
    $durationSec = $record->duration_sec;
    $minutes = floor($durationSec / 60);
    $seconds = $durationSec % 60;
    $durationFormatted = sprintf('%02d:%02d', $minutes, $seconds);
    
    // Status
    $status = $record->status ?? 'ended';
    $statusColor = match($status) {
        'completed', 'ended' => 'success',
        'in_progress' => 'warning',
        'failed' => 'danger',
        default => 'gray'
    };
    $statusLabel = match($status) {
        'completed', 'ended' => 'Beendet',
        'in_progress' => 'Laufend',
        'failed' => 'Fehlgeschlagen',
        default => ucfirst($status)
    };
    
    // Result
    $result = $record->webhook_data['call_analysis']['call_successful'] ?? null;
    $resultLabel = $result === true ? 'Erfolgreich' : ($result === false ? 'Nicht erfolgreich' : '—');
    $resultColor = $result === true ? 'text-green-600' : ($result === false ? 'text-red-600' : 'text-gray-500');
    
    // Cost calculations
    $callCostCents = 0;
    if (isset($record->webhook_data['call_cost'])) {
        if (is_array($record->webhook_data['call_cost'])) {
            $callCostCents = $record->webhook_data['call_cost']['combined_cost'] ?? 
                            $record->webhook_data['call_cost']['total_cost'] ?? 
                            $record->webhook_data['call_cost']['total'] ?? 0;
        } else {
            $callCostCents = floatval($record->webhook_data['call_cost']);
        }
    }
    
    $callCostUSD = $callCostCents / 100;
    $callCostEUR = ExchangeRateService::convertCentsToEur($callCostCents);
    
    // Revenue calculation
    $billingRate = $record->company?->billingRate;
    $companyRate = $billingRate ? $billingRate->rate_per_minute : BillingRate::getDefaultRate();
    
    if ($billingRate) {
        $revenue = $billingRate->calculateCharge($record->duration_sec);
    } else {
        $revenue = ($record->duration_sec / 60) * $companyRate;
    }
    
    $profit = $revenue - $callCostEUR;
    $margin = $revenue > 0 ? ($profit / $revenue * 100) : 0;
    
    // Sentiment
    $sentiment = $record->webhook_data['call_analysis']['user_sentiment'] ?? 
                 ($record->mlPrediction?->sentiment_label ?? 'Unknown');
    $sentimentColor = match(strtolower($sentiment)) {
        'positive' => 'text-green-600',
        'negative' => 'text-red-600',
        default => 'text-gray-600'
    };
    $sentimentLabel = match(strtolower($sentiment)) {
        'positive' => 'Positiv',
        'negative' => 'Negativ',
        'neutral' => 'Neutral',
        default => ucfirst($sentiment)
    };
    
    // Disconnection reason
    $disconnectionReason = match($record->disconnection_reason) {
        'user_hangup' => 'Kunde aufgelegt',
        'agent_hangup' => 'Agent aufgelegt',
        'call_transfer' => 'Weitergeleitet',
        'voicemail' => 'Voicemail',
        'inactivity' => 'Inaktivität',
        default => ucfirst(str_replace('_', ' ', $record->disconnection_reason ?? 'Unknown'))
    };
@endphp

<div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-6">
    {{-- Row 1: Basic Info --}}
    
    {{-- Status & Result --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Status
        </div>
        <div class="flex items-center gap-2">
            <x-filament::badge :color="$statusColor" size="sm">
                {{ $statusLabel }}
            </x-filament::badge>
            @if($result !== null)
                <span class="{{ $resultColor }} text-xs">
                    ({{ $resultLabel }})
                </span>
            @endif
        </div>
    </div>
    
    {{-- Phone Number --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Telefonnummer
        </div>
        <div class="flex items-center gap-2">
            <x-heroicon-m-phone class="w-4 h-4 text-gray-400" />
            <span class="font-mono text-sm">{{ $record->from_number }}</span>
        </div>
    </div>
    
    {{-- Duration --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Dauer
        </div>
        <div class="flex items-center gap-2">
            <x-heroicon-m-clock class="w-4 h-4 text-gray-400" />
            <span class="font-mono text-sm">{{ $durationFormatted }}</span>
            <span class="text-xs text-gray-500">({{ $durationSec }} Sek)</span>
        </div>
    </div>
    
    {{-- Date & Time --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Zeitpunkt
        </div>
        <div>
            <div class="text-sm font-medium">{{ $timestamp->format('d.m.Y') }}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400">{{ $timestamp->format('H:i:s') }} Uhr</div>
        </div>
    </div>
    
    {{-- Row 2: Business Metrics --}}
    
    {{-- Costs & Revenue --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Kosten / Umsatz / Gewinn
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm cursor-help" data-cost-tooltip>
                <span class="text-red-600 font-semibold">{{ number_format($callCostEUR, 2) }}€</span>
                <span class="text-gray-400">/</span>
                <span class="text-blue-600 font-semibold">{{ number_format($revenue, 2) }}€</span>
                <span class="text-gray-400">/</span>
                <span class="{{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                    {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 2) }}€
                </span>
            </span>
            <span class="text-xs text-gray-500">({{ number_format($margin, 0) }}%)</span>
        </div>
    </div>
    
    {{-- Customer Sentiment --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Kundenstimmung
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full {{ match(strtolower($sentiment)) {
                'positive' => 'bg-green-500',
                'negative' => 'bg-red-500',
                default => 'bg-gray-400'
            } }}"></span>
            <span class="{{ $sentimentColor }} text-sm font-medium">{{ $sentimentLabel }}</span>
        </div>
    </div>
    
    {{-- Company/Branch --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Unternehmen / Filiale
        </div>
        <div>
            <div class="text-sm font-medium truncate" title="{{ $record->company?->name }}">
                {{ $record->company?->name ?? 'Unbekannt' }}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $record->branch?->name }}">
                {{ $record->branch?->name ?? 'Hauptfiliale' }}
            </div>
        </div>
    </div>
    
    {{-- Disconnection Reason --}}
    <div class="text-left">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
            Beendet durch
        </div>
        <div class="text-sm">{{ $disconnectionReason }}</div>
    </div>
</div>

{{-- Tooltip for cost details --}}
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<style>
.tippy-box[data-theme~='light'] {
    background-color: white;
    color: #333;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border: 1px solid #e5e7eb;
}
.tippy-box[data-theme~='light'] .tippy-arrow {
    color: white;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const costElement = document.querySelector('[data-cost-tooltip]');
    if (costElement && typeof tippy !== 'undefined') {
        @php
            $productCostsHtml = '';
            if (isset($record->webhook_data['call_cost']['product_costs'])) {
                $productCostsHtml = '<div class="mt-1 text-xs opacity-75">';
                foreach ($record->webhook_data['call_cost']['product_costs'] as $product) {
                    $productName = match($product['product']) {
                        'elevenlabs_tts' => 'ElevenLabs TTS',
                        'gemini_2_0_flash' => 'Gemini 2.0 Flash',
                        'background_voice_cancellation' => 'Rauschunterdrückung',
                        default => ucfirst(str_replace('_', ' ', $product['product']))
                    };
                    $productCostUSD = number_format($product['cost'] / 100, 4);
                    $productCostsHtml .= $productName . ': $' . $productCostUSD . '<br>';
                }
                $productCostsHtml .= '</div>';
            }
        @endphp
        
        tippy(costElement, {
            content: `
                <div class="p-2 text-xs">
                    <div class="font-semibold mb-2 text-gray-900">Kostendetails</div>
                    <div class="space-y-1">
                        <div><span class="text-gray-600">Retell Kosten:</span> <span class="font-mono">${{ number_format($callCostUSD, 4) }} ({{ number_format($callCostEUR, 4) }}€)</span></div>
                        <div><span class="text-gray-600">Wechselkurs:</span> <span class="font-mono">1 USD = {{ number_format(ExchangeRateService::getUsdToEur(), 4) }} EUR</span></div>
                        {!! $productCostsHtml !!}
                    </div>
                    <hr class="my-2 border-gray-200">
                    <div class="space-y-1">
                        <div class="font-semibold text-gray-900">Berechnung</div>
                        <div><span class="text-gray-600">Dauer:</span> <span class="font-mono">{{ $durationSec }} Sek ({{ number_format($durationSec/60, 2) }} Min)</span></div>
                        <div><span class="text-gray-600">Rate:</span> <span class="font-mono">{{ number_format($companyRate, 2) }}€/Min</span></div>
                        <div><span class="text-gray-600">Umsatz:</span> <span class="font-mono font-semibold text-blue-600">{{ number_format($revenue, 2) }}€</span></div>
                        <div><span class="text-gray-600">Abrechnung:</span> {{ $billingRate ? $billingRate->billing_increment_label : 'Sekundengenau' }}</div>
                        <div><span class="text-gray-600">Marge:</span> <span class="font-mono font-semibold {{ $margin >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($margin, 1) }}%</span></div>
                    </div>
                </div>
            `,
            allowHTML: true,
            theme: 'light',
            placement: 'bottom',
            interactive: true,
            maxWidth: 350,
            appendTo: document.body
        });
    }
});
</script>
</div>