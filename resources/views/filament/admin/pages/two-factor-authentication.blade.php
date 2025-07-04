<x-filament-panels::page>
    <div class="space-y-6">
        @if (!auth()->user()->two_factor_secret)
            <x-filament::section>
                <x-slot name="heading">
                    Zwei-Faktor-Authentifizierung ist deaktiviert
                </x-slot>
                
                <x-slot name="description">
                    Aktivieren Sie die Zwei-Faktor-Authentifizierung, um die Sicherheit Ihres Kontos zu erhöhen.
                </x-slot>
                
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p>Wenn die Zwei-Faktor-Authentifizierung aktiviert ist, werden Sie während der Anmeldung nach einem sicheren, zufälligen Token gefragt. Sie können dieses Token aus der Authenticator-App Ihres Telefons abrufen.</p>
                </div>
            </x-filament::section>
        @endif
        
        @if ($showQrCode && auth()->user()->two_factor_secret)
            <x-filament::section>
                <x-slot name="heading">
                    QR-Code scannen
                </x-slot>
                
                <x-slot name="description">
                    Scannen Sie diesen QR-Code mit Ihrer Authenticator-App.
                </x-slot>
                
                <div class="space-y-4">
                    <div class="flex justify-center p-4 bg-white rounded-lg">
                        {!! $this->getTwoFactorQrCodeSvg() !!}
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-semibold mb-2">Oder geben Sie diesen Code manuell ein:</p>
                        <code class="block p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs break-all">
                            {{ decrypt(auth()->user()->two_factor_secret) }}
                        </code>
                    </div>
                    
                    <form wire:submit="confirmTwoFactor" class="space-y-4">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                wire:model="confirmationCode"
                                placeholder="6-stelliger Code aus Ihrer App"
                                autofocus
                                autocomplete="one-time-code"
                                inputmode="numeric"
                                maxlength="6"
                                class="text-center text-2xl tracking-widest"
                            />
                        </x-filament::input.wrapper>
                        
                        <x-filament::button type="submit" class="w-full">
                            2FA bestätigen
                        </x-filament::button>
                    </form>
                </div>
            </x-filament::section>
        @endif
        
        @if ($showRecoveryCodes && auth()->user()->two_factor_recovery_codes)
            <x-filament::section>
                <x-slot name="heading">
                    Recovery Codes
                </x-slot>
                
                <x-slot name="description">
                    Bewahren Sie diese Recovery Codes an einem sicheren Ort auf. Sie können verwendet werden, um auf Ihr Konto zuzugreifen, wenn Ihr Zwei-Faktor-Authentifizierungsgerät verloren geht.
                </x-slot>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-2 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        @foreach ($this->getRecoveryCodes() as $code)
                            <div class="font-mono text-sm">{{ $code['code'] }}</div>
                        @endforeach
                    </div>
                    
                    <div class="flex gap-2">
                        <x-filament::button
                            color="gray"
                            @click="
                                const codes = Array.from(document.querySelectorAll('.font-mono')).map(el => el.textContent).join('\n');
                                const blob = new Blob([codes], { type: 'text/plain' });
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'askproai-recovery-codes.txt';
                                a.click();
                                URL.revokeObjectURL(url);
                            "
                        >
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                            Recovery Codes herunterladen
                        </x-filament::button>
                        
                        <x-filament::button
                            color="gray"
                            @click="
                                const codes = Array.from(document.querySelectorAll('.font-mono')).map(el => el.textContent).join('\n');
                                navigator.clipboard.writeText(codes);
                                $tooltip('Recovery Codes kopiert!');
                            "
                        >
                            <x-heroicon-o-clipboard class="w-4 h-4 mr-2" />
                            In Zwischenablage kopieren
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
        
        @if (auth()->user()->two_factor_confirmed_at)
            <x-filament::section>
                <x-slot name="heading">
                    Zwei-Faktor-Authentifizierung ist aktiv
                </x-slot>
                
                <x-slot name="description">
                    Ihr Konto ist durch Zwei-Faktor-Authentifizierung geschützt.
                </x-slot>
                
                <div class="space-y-4">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Status:</dt>
                            <dd class="font-medium text-success-600 dark:text-success-400">
                                <x-heroicon-o-check-circle class="w-4 h-4 inline-block mr-1" />
                                Aktiv
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Aktiviert am:</dt>
                            <dd class="font-medium">{{ auth()->user()->two_factor_confirmed_at->format('d.m.Y H:i') }}</dd>
                        </div>
                        @if (auth()->user()->two_factor_method)
                            <div class="flex justify-between">
                                <dt class="text-gray-600 dark:text-gray-400">Methode:</dt>
                                <dd class="font-medium">{{ auth()->user()->two_factor_method === 'sms' ? 'SMS' : 'Authenticator App' }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>