<x-filament::widget>
    <style>
        .stripe-glassy {
            background: rgba(240,240,255,0.97);
            border-radius: 2rem;
            box-shadow: 0 4px 32px 0 #a78bfa2b, 0 1px 4px #7c3aed44;
            backdrop-filter: blur(5px) saturate(130%);
            border: 1.5px solid #e9d5ff;
            transition: box-shadow .21s, transform .22s;
        }
        .stripe-glassy:hover {
            box-shadow: 0 12px 40px 0 #a78bfa44, 0 6px 18px #7c3aed22;
            transform: scale(1.025) translateY(-3px);
        }
        .stripe-anim-bar {
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, #a78bfa, #38bdf8, #34d399, #fbbf24, #f43f5e, #a78bfa, #a78bfa);
            background-size: 200% 100%;
            animation: stripe-bar-move 1.7s linear infinite;
            border-radius: 2px;
        }
        @keyframes stripe-bar-move {
            0% {background-position: 0 0;}
            100% {background-position: 100% 0;}
        }
    </style>
    @php $stripe = $this->getStripeData(); @endphp
    <div class="stripe-glassy p-8 mb-6 w-full max-w-2xl mx-auto shadow-lg flex flex-col gap-4 items-center relative">
        <h2 class="text-2xl font-black flex items-center gap-3 tracking-tight mb-3">
            <svg width="32" height="32" viewBox="0 0 48 48" class="text-purple-700 drop-shadow">
                <rect x="5" y="10" width="38" height="28" rx="7" fill="#7c3aed"/>
            </svg>
            <span class="text-purple-800">Stripe Systemstatus</span>
            <span class="ml-3 text-base text-purple-700 font-extrabold tracking-widest animate-pulse">LIVE</span>
        </h2>
        <div class="stripe-anim-bar mb-4"></div>
        <div class="flex flex-row gap-12 justify-center w-full">
            <div>
                <div class="text-sm text-gray-500 mb-1">Kontostand</div>
                <div class="text-2xl font-extrabold text-purple-800 mb-1">
                    {{ $stripe['balance'] !== null ? number_format($stripe['balance'], 2, ',', '.') . ' ' . $stripe['currency'] : 'n/a' }}
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500 mb-1">Letzte Zahlung</div>
                <div class="text-xl font-bold text-purple-900 mb-0">
                    {{ $stripe['last_payment'] ? number_format($stripe['last_payment'], 2, ',', '.') : 'n/a' }} {{ $stripe['currency'] ?? '' }}
                </div>
                <div class="text-xs text-gray-400">{{ $stripe['last_payment_at'] ?? 'n/a' }}</div>
                <div class="text-xs font-bold mt-1">
                    Status:
                    @if($stripe['last_payment_status'] === 'succeeded')
                        <span class="text-green-600 animate-pulse">erfolgreich</span>
                    @elseif($stripe['last_payment_status'])
                        <span class="text-red-600 animate-pulse">{{ $stripe['last_payment_status'] }}</span>
                    @else
                        <span class="text-gray-400">n/a</span>
                    @endif
                </div>
            </div>
        </div>
        {{-- Chart --}}
        @if(count($stripe['payment_chart']))
            <div class="w-full pt-4">
                <canvas id="stripe-payments-chart" height="36"></canvas>
                <script>
                    setTimeout(() => {
                        if(document.getElementById("stripe-payments-chart")) {
                            new Chart(document.getElementById("stripe-payments-chart").getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: {!! json_encode(array_column($stripe['payment_chart'], 'date')) !!},
                                    datasets: [{
                                        label: 'Letzte Zahlungen',
                                        data: {!! json_encode(array_column($stripe['payment_chart'], 'amount')) !!},
                                        backgroundColor: {!! json_encode(array_map(function($p) { return $p['status']==='succeeded' ? '#34d399' : '#ef4444'; }, $stripe['payment_chart'])) !!},
                                        borderRadius: 7,
                                        borderSkipped: false,
                                        maxBarThickness: 30,
                                    }]
                                },
                                options: {
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: { beginAtZero: true, display: false },
                                        x: { grid: {display:false}, display: true }
                                    },
                                    animation: { duration: 1000, easing: "easeOutCirc" },
                                    responsive: true,
                                }
                            });
                        }
                    }, 200);
                </script>
            </div>
        @endif

        @if(!empty($stripe['errors']))
            <div class="text-red-600 text-sm mt-4 border border-red-100 bg-red-50 rounded px-3 py-2">Fehler: {{ implode('; ', $stripe['errors']) }}</div>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</x-filament::widget>
