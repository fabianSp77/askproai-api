@php
    use App\Models\BalanceTopup;
    use Carbon\Carbon;
    
    $record = $getRecord();
    $topups = BalanceTopup::where('company_id', $record->company_id)
        ->latest()
        ->limit(20)
        ->get();
@endphp

<div class="space-y-4">
    <div class="flex justify-between items-center mb-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Aufladungsverlauf</h4>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            Letzte 20 Aufladungen
        </span>
    </div>
    
    @forelse($topups as $topup)
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-3">
                {{-- Status Icon --}}
                <div class="flex-shrink-0">
                    @switch($topup->status)
                        @case('completed')
                            <div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                                <x-heroicon-s-check-circle class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            @break
                        @case('processing')
                            <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/20 flex items-center justify-center">
                                <x-heroicon-s-clock class="h-5 w-5 text-amber-600 dark:text-amber-400 animate-pulse" />
                            </div>
                            @break
                        @case('failed')
                            <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                                <x-heroicon-s-x-circle class="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                            @break
                        @default
                            <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <x-heroicon-s-question-mark-circle class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </div>
                    @endswitch
                </div>
                
                {{-- Details --}}
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ number_format($topup->amount, 2) }}€
                        </span>
                        @if($topup->bonus_amount > 0)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                +{{ number_format($topup->bonus_amount, 2) }}€ Bonus
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $topup->created_at->format('d.m.Y H:i') }}
                        @if($topup->initiated_by_name)
                            • {{ $topup->initiated_by_name }}
                        @endif
                    </div>
                    
                    @if($topup->status === 'failed' && $topup->error_message)
                        <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                            {{ $topup->error_message }}
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Actions --}}
            <div class="flex items-center gap-2">
                @if($topup->status === 'completed' && $topup->invoice_url)
                    <a href="{{ $topup->invoice_url }}" 
                       target="_blank"
                       class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors">
                        <x-heroicon-o-document-arrow-down class="h-5 w-5" />
                    </a>
                @endif
                
                @if($topup->stripe_checkout_session_id)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Stripe
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            Keine Aufladungen vorhanden
        </div>
    @endforelse
    
    {{-- Statistiken --}}
    @if($topups->count() > 0)
        <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Gesamt aufgeladen</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($topups->where('status', 'completed')->sum('amount'), 2) }}€
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Bonus erhalten</div>
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400">
                    {{ number_format($topups->where('status', 'completed')->sum('bonus_amount'), 2) }}€
                </div>
            </div>
        </div>
    @endif
</div>