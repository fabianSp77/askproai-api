<div class="space-y-4">
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 space-y-3">
        <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">Empfänger:</span>
            <span class="font-medium text-gray-900 dark:text-white">
                {{ $record->partnerCompany->partner_billing_email ?? 'Nicht konfiguriert' }}
            </span>
        </div>
        @if($record->partnerCompany->partner_billing_cc_emails)
            <div class="flex justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">CC:</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ implode(', ', $record->partnerCompany->partner_billing_cc_emails) }}
                </span>
            </div>
        @endif
        <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">Partner:</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $record->partnerCompany->name }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">Rechnungs-Nr.:</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $record->invoice_number }}</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
            <span class="text-sm text-gray-500 dark:text-gray-400">Betrag:</span>
            <span class="font-bold text-lg text-primary-600 dark:text-primary-400">
                {{ number_format($record->total, 2, ',', '.') }} €
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">Fälligkeit:</span>
            <span class="font-medium text-gray-900 dark:text-white">
                {{ now()->addDays($record->partnerCompany->partner_payment_terms_days ?? 14)->format('d.m.Y') }}
            </span>
        </div>
    </div>

    @if(!$record->partnerCompany->partner_billing_email)
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
            <x-heroicon-o-exclamation-circle class="h-5 w-5 flex-shrink-0" />
            <span>Kein Rechnungsempfänger konfiguriert! Bitte zuerst in den Partner-Einstellungen hinterlegen.</span>
        </div>
    @else
        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-600 dark:text-amber-400 flex items-center gap-2">
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 flex-shrink-0" />
            <span>Nach Versand kann die Rechnung nicht mehr bearbeitet werden.</span>
        </div>
    @endif
</div>
