<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <x-filament::avatar
                :src="filament()->getUserAvatarUrl($user)"
                :alt="$user->name"
                :size="'lg'"
            />

            <div class="flex-1">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $user->name }}
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $user->email }}
                </p>

                @if($user->company)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $user->company->name }}
                    </p>
                @endif
            </div>

            <div class="flex gap-2">
                @if(filament()->hasProfile())
                    <x-filament::link
                        :href="filament()->getProfileUrl()"
                        color="gray"
                        icon="heroicon-m-cog-6-tooth"
                        icon-position="before"
                        size="sm"
                    >
                        {{ __('filament-panels::widgets/account-widget.actions.manage_account.label') ?? 'Manage account' }}
                    </x-filament::link>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>