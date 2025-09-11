<x-filament-panels::page.simple>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        <div class="grid gap-y-4">
            <div>
                <label for="email" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        E-Mail-Adresse <sup class="text-danger-600 dark:text-danger-400 font-medium">*</sup>
                    </span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="data.email"
                    wire:model="data.email"
                    required
                    autocomplete="email"
                    autofocus
                    class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:text-gray-400 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                    placeholder="E-Mail-Adresse eingeben"
                />
                @error('data.email')
                    <p class="fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="password" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        Passwort <sup class="text-danger-600 dark:text-danger-400 font-medium">*</sup>
                    </span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="data.password"
                    wire:model="data.password"
                    required
                    autocomplete="current-password"
                    class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:text-gray-400 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                    placeholder="Passwort eingeben"
                />
                @error('data.password')
                    <p class="fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                    <input
                        type="checkbox"
                        id="remember"
                        name="data.remember"
                        wire:model="data.remember"
                        class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm outline-none focus:ring-2 focus:ring-primary-500 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:border-white/10 dark:bg-white/5 dark:checked:bg-primary-500 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600"
                    />
                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        Angemeldet bleiben
                    </span>
                </label>
            </div>
        </div>

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>