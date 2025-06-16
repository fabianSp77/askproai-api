<x-filament::widget>
    <style>
        .glassy {
            background: rgba(255,255,255,0.8);
            border-radius: 2rem;
            box-shadow: 0 6px 40px 0 rgba(34,197,94,0.10), 0 2px 6px rgba(30,41,59,0.08);
            backdrop-filter: blur(7px) saturate(130%);
            border: 1.2px solid rgba(180,225,255,0.33);
            transition: box-shadow .23s, transform .22s;
        }
        .glassy:hover {
            box-shadow: 0 16px 48px 0 #38bdf8a2, 0 4px 14px #22d3ee22;
            transform: scale(1.03) translateY(-4px);
        }
        .glow-status {
            box-shadow: 0 0 0 0 #22d3ee, 0 0 20px 2px #a7f3d0, 0 0 50px 10px #6ee7b7;
            animation: glow-pulse 1.1s infinite alternate;
        }
        @keyframes glow-pulse {
            0% { box-shadow: 0 0 0 0 #22d3ee, 0 0 10px 1px #a7f3d0, 0 0 20px 3px #6ee7b7;}
            100% { box-shadow: 0 0 0 0 #22c55e, 0 0 30px 6px #4ade80, 0 0 90px 20px #bbf7d0;}
        }
        .live-bar {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, #fbbf24, #34d399, #3b82f6, #f43f5e, #a78bfa, #34d399, #fbbf24);
            background-size: 200% 100%;
            animation: live-bar-move 2.2s linear infinite;
            border-radius: 2px;
        }
        @keyframes live-bar-move {
            0% {background-position: 0 0;}
            100% {background-position: 100% 0;}
        }
    </style>
    <div class="w-full flex flex-col items-center">
        <div class="w-full">
            <h2 class="text-2xl font-black mb-7 flex items-center gap-3 tracking-tight">
                <svg class="w-8 h-8 text-yellow-400 status-glow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="uppercase tracking-wide text-gray-900">System & Integrationsstatus</span>
                <span class="ml-3 text-base text-cyan-600 font-extrabold tracking-widest animate-pulse">LIVE</span>
            </h2>
        </div>
        <div class="live-bar mb-7"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-10 w-full max-w-6xl">
            @foreach($this->getIntegrationStates() as $name => $info)
                <div class="glassy flex flex-col items-center text-center p-8 relative overflow-hidden min-h-[210px]">
                    <div class="mb-6 flex justify-center items-center w-full">
                        @if($info['flowing'])
                            <span class="inline-block">
                                <svg width="68" height="68" class="glow-status" viewBox="0 0 68 68" fill="none">
                                    <circle cx="34" cy="34" r="26" fill="#22c55e" />
                                    <circle cx="34" cy="34" r="31" fill="#22c55e" fill-opacity="0.09">
                                        <animate attributeName="r" values="29;34;29" dur="1.3s" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                            </span>
                        @elseif($info['status'] === 'online')
                            <span class="inline-block">
                                <svg width="68" height="68" viewBox="0 0 68 68" fill="none">
                                    <circle cx="34" cy="34" r="26" fill="#34d399" />
                                    <circle cx="34" cy="34" r="31" fill="#34d399" fill-opacity="0.10"/>
                                </svg>
                            </span>
                        @else
                            <span class="inline-block">
                                <svg width="68" height="68" viewBox="0 0 68 68" fill="none">
                                    <circle cx="34" cy="34" r="26" fill="#ef4444" />
                                    <circle cx="34" cy="34" r="31" fill="#ef4444" fill-opacity="0.10">
                                        <animate attributeName="r" values="29;36;29" dur="1.1s" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                            </span>
                        @endif
                    </div>
                    <div class="text-lg font-bold text-gray-900 flex items-center justify-center gap-2 mb-2 select-none">
                        @if(Str::contains($name, 'Cal.com'))
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M8 2v4M16 2v4M3 10h18" stroke="currentColor" stroke-width="2"/></svg>
                        @elseif(Str::contains($name, 'Retell'))
                            <svg class="w-7 h-7 text-pink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92V19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"><path d="M16 5V3a2 2 0 1 1 4 0v2M6 8V6a2 2 0 1 0-4 0v2" stroke="currentColor" stroke-width="2"/></svg>
                        @elseif(Str::contains($name, 'Datenbank'))
                            <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5" /><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/></svg>
                        @elseif(Str::contains($name, 'Server'))
                            <svg class="w-7 h-7 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="4"/><path d="M6 8h.01M6 16h.01"/></svg>
                        @endif
                        <span>{{ $name }}</span>
                    </div>
                    <div class="text-xs text-gray-500 font-mono mb-1">
                        @if($info['last_success'])
                            Zuletzt: {{ \Carbon\Carbon::parse($info['last_success'])->diffForHumans() }}
                        @else
                            Noch kein Erfolg
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Antwortzeit: <b>{{ $info['latency'] ?? 'n/a' }} ms</b>
                    </div>
                    <div class="text-lg font-extrabold mb-1 tracking-wide">
                        Status:
                        @if($info['status'] === 'online')
                            <span class="text-green-600 animate-pulse">Online</span>
                        @else
                            <span class="text-red-600 animate-pulse">Offline</span>
                        @endif
                    </div>
                    @if(!empty($info['history']))
                        <canvas id="chart-{{ md5($name) }}" width="140" height="34" class="mt-5 z-10"></canvas>
                        <script>
                            setTimeout(() => {
                                if(document.getElementById("chart-{{ md5($name) }}")) {
                                    new Chart(document.getElementById("chart-{{ md5($name) }}").getContext('2d'), {
                                        type: 'line',
                                        data: {
                                            labels: {!! json_encode(array_map(fn($t) => date('H:i', $t), $info['history'])) !!},
                                            datasets: [{
                                                data: {!! json_encode(array_map(fn($t) => 1, $info['history'])) !!},
                                                borderColor: "{{ $info['status']==='online' ? '#22c55e' : '#ef4444' }}",
                                                backgroundColor: 'rgba(34,197,94,0.18)',
                                                borderWidth: 3,
                                                pointRadius: 0,
                                                fill: true,
                                                tension: 0.4
                                            }]
                                        },
                                        options: {
                                            plugins: { legend: { display: false } },
                                            scales: { y: { display: false }, x: { display: false } },
                                            responsive: false,
                                            animation: {
                                                duration: 900,
                                                easing: "easeOutCirc"
                                            }
                                        }
                                    });
                                }
                            }, 150);
                        </script>
                    @endif
                    <div class="absolute top-2 right-5 opacity-0 group-hover:opacity-100 transition pointer-events-none text-xs text-cyan-600 font-black select-none">LIVE</div>
                </div>
            @endforeach
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</x-filament::widget>
