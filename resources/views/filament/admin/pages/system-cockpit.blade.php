<x-filament::page>
    <div class="flex flex-col w-full items-center gap-10">
        <div class="w-full max-w-6xl mx-auto">
            <h2 class="text-3xl font-black mb-6 flex items-center gap-4 tracking-tight">
                <span class="text-yellow-400 animate-pulse">⚡</span>
                System Cockpit
                <span class="ml-3 text-base text-cyan-600 font-extrabold tracking-widest animate-pulse">LIVE Monitoring</span>
            </h2>
            <div class="h-2 w-full rounded bg-gradient-to-r from-yellow-300 via-green-400 to-pink-400 animate-pulse mb-4"></div>
            <!-- Grid für große Status-Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                @livewire(\App\Filament\Widgets\AnimatedStatusWidget::class)
                @livewire(\App\Filament\Widgets\StripeStatusWidget::class)
            </div>
            <!-- Grid für kleine Status-Widgets -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @livewire(\App\Filament\Widgets\MailStatusWidget::class)
                @livewire(\App\Filament\Widgets\LogStatusWidget::class)
                @livewire(\App\Filament\Widgets\QueueStatusWidget::class)
                @livewire(\App\Filament\Widgets\BackupStatusWidget::class)
                @livewire(\App\Filament\Widgets\MiddlewareStatusWidget::class)
            </div>
        </div>
    </div>
</x-filament::page>
