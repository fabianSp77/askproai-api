<x-filament-widgets::widget>
    <x-filament::card>
        <div class="relative">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-5 h-5" />
                    Compliance Status
                </h2>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-{{ $overall_compliance >= 80 ? 'success' : ($overall_compliance >= 60 ? 'warning' : 'danger') }}-600">
                        {{ $overall_compliance }}%
                    </span>
                </div>
            </div>

            {{-- Compliance Areas --}}
            <div class="space-y-4">
                {{-- DSGVO/GDPR --}}
                <div class="border rounded-lg p-3 {{ $gdpr['status'] === 'compliant' ? 'border-success-200 bg-success-50' : ($gdpr['status'] === 'warning' ? 'border-warning-200 bg-warning-50' : 'border-danger-200 bg-danger-50') }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-lock-closed class="w-4 h-4 {{ $gdpr['status'] === 'compliant' ? 'text-success-600' : ($gdpr['status'] === 'warning' ? 'text-warning-600' : 'text-danger-600') }}" />
                                <h3 class="font-medium text-sm">DSGVO/GDPR</h3>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 space-y-1">
                                <div>Einwilligungen: {{ $gdpr['consent_rate'] }}%</div>
                                <div class="flex items-center gap-2">
                                    <span>Datenlöschung:</span>
                                    @if($gdpr['data_retention'])
                                        <x-heroicon-o-check-circle class="w-3 h-3 text-success-600" />
                                    @else
                                        <x-heroicon-o-x-circle class="w-3 h-3 text-danger-600" />
                                    @endif
                                </div>
                                <div class="text-gray-500">Letztes Audit: {{ $gdpr['last_audit'] }}</div>
                            </div>
                        </div>
                        <div class="text-xs font-medium px-2 py-1 rounded-full {{ $gdpr['status'] === 'compliant' ? 'bg-success-100 text-success-700' : ($gdpr['status'] === 'warning' ? 'bg-warning-100 text-warning-700' : 'bg-danger-100 text-danger-700') }}">
                            {{ $gdpr['status'] === 'compliant' ? 'Konform' : ($gdpr['status'] === 'warning' ? 'Warnung' : 'Kritisch') }}
                        </div>
                    </div>
                </div>

                {{-- Kassenbuch --}}
                <div class="border rounded-lg p-3 {{ $kassenbuch['status'] === 'synced' ? 'border-success-200 bg-success-50' : ($kassenbuch['status'] === 'partial' ? 'border-warning-200 bg-warning-50' : 'border-danger-200 bg-danger-50') }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-calculator class="w-4 h-4 {{ $kassenbuch['status'] === 'synced' ? 'text-success-600' : ($kassenbuch['status'] === 'partial' ? 'text-warning-600' : 'text-danger-600') }}" />
                                <h3 class="font-medium text-sm">Kassenbuch</h3>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 space-y-1">
                                <div>Sync-Rate: {{ $kassenbuch['sync_rate'] }}%</div>
                                <div>Heutiger Umsatz: {{ number_format($kassenbuch['today_revenue'], 2, ',', '.') }} €</div>
                                @if($kassenbuch['pending_entries'] > 0)
                                    <div class="text-warning-600">{{ $kassenbuch['pending_entries'] }} ausstehende Einträge</div>
                                @endif
                                <div class="text-gray-500">Letzte Sync: {{ $kassenbuch['last_sync'] }} Uhr</div>
                            </div>
                        </div>
                        <div class="text-xs font-medium px-2 py-1 rounded-full {{ $kassenbuch['status'] === 'synced' ? 'bg-success-100 text-success-700' : ($kassenbuch['status'] === 'partial' ? 'bg-warning-100 text-warning-700' : 'bg-danger-100 text-danger-700') }}">
                            {{ $kassenbuch['status'] === 'synced' ? 'Synchron' : ($kassenbuch['status'] === 'partial' ? 'Teilweise' : 'Fehler') }}
                        </div>
                    </div>
                </div>

                {{-- Steuern --}}
                <div class="border rounded-lg p-3 {{ $tax['status'] === 'on_track' ? 'border-success-200 bg-success-50' : ($tax['status'] === 'urgent' ? 'border-warning-200 bg-warning-50' : 'border-danger-200 bg-danger-50') }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-document-text class="w-4 h-4 {{ $tax['status'] === 'on_track' ? 'text-success-600' : ($tax['status'] === 'urgent' ? 'text-warning-600' : 'text-danger-600') }}" />
                                <h3 class="font-medium text-sm">USt-Voranmeldung</h3>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 space-y-1">
                                <div>Periode: {{ $tax['current_period'] }}</div>
                                <div>Nettoumsatz: {{ number_format($tax['monthly_revenue'], 2, ',', '.') }} €</div>
                                <div>USt (19%): {{ number_format($tax['vat_amount'], 2, ',', '.') }} €</div>
                                <div class="{{ $tax['days_until_deadline'] <= 5 ? 'text-danger-600 font-medium' : 'text-gray-500' }}">
                                    Frist: {{ $tax['deadline'] }} ({{ $tax['days_until_deadline'] }} Tage)
                                </div>
                            </div>
                        </div>
                        <div class="text-xs font-medium px-2 py-1 rounded-full {{ $tax['status'] === 'on_track' ? 'bg-success-100 text-success-700' : ($tax['status'] === 'urgent' ? 'bg-warning-100 text-warning-700' : 'bg-danger-100 text-danger-700') }}">
                            {{ $tax['status'] === 'on_track' ? 'Planmäßig' : ($tax['status'] === 'urgent' ? 'Dringend' : 'Überfällig') }}
                        </div>
                    </div>
                </div>

                {{-- Datensicherheit --}}
                <div class="border rounded-lg p-3 {{ $data_security['status'] === 'secure' ? 'border-success-200 bg-success-50' : ($data_security['status'] === 'warning' ? 'border-warning-200 bg-warning-50' : 'border-danger-200 bg-danger-50') }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-server class="w-4 h-4 {{ $data_security['status'] === 'secure' ? 'text-success-600' : ($data_security['status'] === 'warning' ? 'text-warning-600' : 'text-danger-600') }}" />
                                <h3 class="font-medium text-sm">Datensicherheit</h3>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 space-y-1">
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center gap-1">
                                        @if($data_security['encryption'])
                                            <x-heroicon-o-check-circle class="w-3 h-3 text-success-600" />
                                        @else
                                            <x-heroicon-o-x-circle class="w-3 h-3 text-danger-600" />
                                        @endif
                                        Verschlüsselung
                                    </span>
                                    <span class="flex items-center gap-1">
                                        @if($data_security['ssl_certificate'])
                                            <x-heroicon-o-check-circle class="w-3 h-3 text-success-600" />
                                        @else
                                            <x-heroicon-o-x-circle class="w-3 h-3 text-danger-600" />
                                        @endif
                                        SSL
                                    </span>
                                </div>
                                <div>Letztes Backup: {{ $data_security['last_backup'] }}</div>
                                @if($data_security['backup_age_hours'] > 24)
                                    <div class="text-warning-600">Backup {{ $data_security['backup_age_hours'] }}h alt!</div>
                                @endif
                            </div>
                        </div>
                        <div class="text-xs font-medium px-2 py-1 rounded-full {{ $data_security['status'] === 'secure' ? 'bg-success-100 text-success-700' : ($data_security['status'] === 'warning' ? 'bg-warning-100 text-warning-700' : 'bg-danger-100 text-danger-700') }}">
                            {{ $data_security['status'] === 'secure' ? 'Sicher' : ($data_security['status'] === 'warning' ? 'Warnung' : 'Kritisch') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Next Actions --}}
            @if(count($next_actions) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Erforderliche Maßnahmen</h3>
                    <div class="space-y-2">
                        @foreach($next_actions as $action)
                            <div class="flex items-center gap-2 text-xs">
                                <x-heroicon-o-exclamation-triangle 
                                    class="w-4 h-4 {{ $action['priority'] === 'urgent' ? 'text-danger-600' : ($action['priority'] === 'high' ? 'text-warning-600' : 'text-info-600') }}" 
                                />
                                <span class="flex-1">{{ $action['action'] }}</span>
                                @if(isset($action['count']))
                                    <span class="font-medium">{{ $action['count'] }}</span>
                                @endif
                                @if(isset($action['deadline']))
                                    <span class="text-danger-600 font-medium">{{ $action['deadline'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament-widgets::widget>