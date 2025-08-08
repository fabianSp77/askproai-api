{{-- MCP Configuration Page --}}
<x-filament-panels::page>
    {{-- Include custom styles --}}
    @push('styles')
        <style>
            /* Ensure full width layout */
            .fi-section-content-ctn {
                max-width: none !important;
            }
            
            /* Custom scrollbar for better UX */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
        </style>
    @endpush

    {{-- Meta tags for React component --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="tenant-id" content="{{ auth()->user()->company_id ?? 1 }}">
    <meta name="api-base-url" content="{{ config('app.url') }}/api/mcp">
    <meta name="admin-api-url" content="{{ config('app.url') }}/admin/api/mcp">

    {{-- React Component Container --}}
    <div 
        id="mcp-configuration-root" 
        class="w-full min-h-screen"
        data-mount-data="{{ json_encode($mountData ?? []) }}"
    >
        {{-- Loading State --}}
        <div class="flex items-center justify-center h-64">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-lg text-gray-600">Loading MCP Configuration...</span>
            </div>
        </div>
    </div>

    {{-- Fallback content for when React fails to load --}}
    <noscript>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 m-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">JavaScript Required</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>The MCP Configuration interface requires JavaScript to function properly. Please enable JavaScript in your browser and refresh the page.</p>
                    </div>
                </div>
            </div>
        </div>
    </noscript>

    {{-- Scripts --}}
    @push('scripts')
        {{-- Add Vite assets --}}
        @vite(['resources/js/mcp-configuration-mount.jsx', 'resources/css/mcp-configuration.css'])

        {{-- Error Handling and Debugging --}}
        <script>
            // Add debugging info
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const container = document.getElementById('mcp-configuration-root');
                    if (container && container.innerHTML.includes('Loading MCP Configuration')) {
                        console.error('MCP Configuration failed to mount after 5 seconds');
                        
                        // Show error message
                        container.innerHTML = `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-6 m-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Failed to Load</h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <p>The MCP Configuration interface failed to load. Please check the browser console for errors.</p>
                                            <p class="mt-2">
                                                <a href="/admin/mcp-control" 
                                                   class="font-medium text-red-700 underline hover:text-red-600">
                                                    Try the legacy MCP Control Center instead
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        console.log('MCP Configuration loaded successfully');
                    }
                }, 5000);
            });

            // Global error handler
            window.addEventListener('error', function(e) {
                if (e.filename && e.filename.includes('mcp-configuration')) {
                    console.error('MCP Configuration Error:', e.error);
                }
            });
        </script>

        {{-- WebSocket initialization for real-time updates --}}
        @if(config('broadcasting.default') !== 'null')
            <script>
                // Initialize Echo for real-time updates
                if (window.Echo) {
                    window.Echo.channel('mcp-metrics')
                        .listen('MCPMetricsUpdated', (data) => {
                            window.dispatchEvent(new CustomEvent('mcp-metrics-updated', { 
                                detail: data 
                            }));
                        })
                        .listen('MCPCallCompleted', (data) => {
                            window.dispatchEvent(new CustomEvent('mcp-call-completed', { 
                                detail: data 
                            }));
                        })
                        .listen('CircuitBreakerStateChanged', (data) => {
                            window.dispatchEvent(new CustomEvent('circuit-breaker-changed', { 
                                detail: data 
                            }));
                        });
                }
            </script>
        @endif
    @endpush
</x-filament-panels::page>