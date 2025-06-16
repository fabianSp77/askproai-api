<x-filament::page>
    <h1 class="text-3xl font-extrabold mb-8 flex items-center gap-3">
        <svg width="38" height="38" class="text-yellow-400" viewBox="0 0 24 24" fill="none">
            <path stroke="#fbbf24" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <span>System Cockpit</span>
        <span class="ml-3 text-sm text-cyan-500 animate-pulse">LIVE Monitoring</span>
    </h1>
    <x-filament::card>
        @livewire(\App\Filament\Widgets\AnimatedStatusWidget::class)
        @livewire(\App\Filament\Widgets\StripeStatusWidget::class)
        @livewire(\App\Filament\Widgets\MailStatusWidget::class)
        @livewire(\App\Filament\Widgets\LogStatusWidget::class)
        @livewire(\App\Filament\Widgets\QueueStatusWidget::class)
        @livewire(\App\Filament\Widgets\BackupStatusWidget::class)
        @livewire(\App\Filament\Widgets\MiddlewareStatusWidget::class)
    </x-filament::card>
</x-filament::page>
