<div class="w-full">
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
        
        // Language detection
        $detectedLanguage = $record->detected_language ?? null;
        $languageConfidence = $record->language_confidence ?? 0;
        $languageMismatch = $record->language_mismatch ?? false;
        $companyLanguage = $record->company?->default_language ?? 'de';
        
        $languageLabel = match($detectedLanguage) {
            'de' => '🇩🇪 Deutsch',
            'en' => '🇬🇧 English',
            'fr' => '🇫🇷 Français',
            'es' => '🇪🇸 Español',
            'it' => '🇮🇹 Italiano',
            default => $detectedLanguage ? strtoupper($detectedLanguage) : 'Nicht erkannt'
        };
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-6">
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
        
        {{-- Costs & Revenue --}}
        <div class="text-left">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                Kosten / Umsatz / Gewinn
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm">
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
        
        {{-- Language Detection --}}
        <div class="text-left">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                Sprache
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">{{ $languageLabel }}</span>
                    @if($detectedLanguage)
                        <span class="text-xs text-gray-500">({{ number_format($languageConfidence * 100, 0) }}%)</span>
                    @endif
                </div>
                @if($languageMismatch)
                    <div class="flex items-center gap-1 mt-1">
                        <x-heroicon-m-exclamation-triangle class="w-3 h-3 text-yellow-600" />
                        <span class="text-xs text-yellow-600">
                            Erwartet: {{ strtoupper($companyLanguage) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>