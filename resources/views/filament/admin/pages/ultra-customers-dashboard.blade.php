<x-filament-panels::page>
    @vite(['resources/css/filament/admin/ultra-customers.css'])
    
    {{-- Header with Smart Search --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">👥 Customer Intelligence Hub</h1>
            <div class="flex items-center gap-2">
                <button onclick="openImportModal()" class="ultra-customer-action">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Import
                </button>
                <button onclick="exportCustomers()" class="ultra-customer-action">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export
                </button>
                <button onclick="openNewCustomerModal()" class="ultra-customer-action primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Customer
                </button>
            </div>
        </div>
        
        {{-- Smart Search Bar --}}
        <div class="ultra-customer-search">
            <svg class="ultra-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input 
                type="text" 
                class="ultra-search-input" 
                placeholder="🔍 Search by name, phone, email or use natural language like 'VIP customers from Berlin' or 'customers who haven't visited in 30 days'..."
                onkeyup="performSmartSearch(this.value)"
            >
        </div>
    </div>
    
    {{-- Statistics Overview --}}
    <div class="ultra-customer-stats">
        <div class="ultra-customer-stat">
            <div class="ultra-customer-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Total Customers</div>
                <div class="mt-1 text-3xl font-bold">{{ $totalCustomers ?? 2847 }}</div>
                <div class="mt-2 flex items-center text-sm">
                    <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-green-600">+12% growth</span>
                </div>
            </div>
        </div>
        
        <div class="ultra-customer-stat">
            <div class="ultra-customer-stat-icon" style="background: #3B82F6;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">New (30 days)</div>
                <div class="mt-1 text-3xl font-bold">{{ $newCustomers ?? 142 }}</div>
                <div class="mt-2 flex items-center text-sm">
                    <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-green-600">+18% increase</span>
                </div>
            </div>
        </div>
        
        <div class="ultra-customer-stat">
            <div class="ultra-customer-stat-icon" style="background: #F59E0B;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">VIP Customers</div>
                <div class="mt-1 text-3xl font-bold">{{ $vipCustomers ?? 89 }}</div>
                <div class="mt-2 text-sm text-amber-600">⭐ Elite tier</div>
            </div>
        </div>
        
        <div class="ultra-customer-stat">
            <div class="ultra-customer-stat-icon" style="background: #EF4444;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">At Risk</div>
                <div class="mt-1 text-3xl font-bold">{{ $atRiskCustomers ?? 23 }}</div>
                <div class="mt-2 text-sm text-red-600">⚠️ Needs attention</div>
            </div>
        </div>
    </div>
    
    {{-- View Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button onclick="switchView('all')" class="view-tab active" data-view="all">
                All Customers
            </button>
            <button onclick="switchView('segments')" class="view-tab" data-view="segments">
                Segments
            </button>
            <button onclick="switchView('timeline')" class="view-tab" data-view="timeline">
                Timeline
            </button>
            <button onclick="switchView('map')" class="view-tab" data-view="map">
                Map View
            </button>
            <button onclick="switchView('analytics')" class="view-tab" data-view="analytics">
                Analytics
            </button>
        </nav>
    </div>
    
    {{-- Main Content Area --}}
    <div id="content-area">
        {{-- All Customers View --}}
        <div id="all-view" class="view-content">
            <div class="space-y-4">
                @forelse($customers ?? [] as $customer)
                    <div class="ultra-customer-card">
                        <div class="ultra-customer-header">
                            <div class="ultra-customer-avatar {{ $customer->is_vip ? 'vip' : '' }}">
                                {{ substr($customer->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="ultra-customer-info">
                                <h3 class="ultra-customer-name">{{ $customer->name ?? 'Unknown' }}</h3>
                                <div class="ultra-customer-contact">
                                    <span>📱 {{ $customer->phone ?? 'No phone' }}</span>
                                    <span>📧 {{ $customer->email ?? 'No email' }}</span>
                                </div>
                            </div>
                            <span class="ultra-customer-badge {{ $customer->customer_type ?? 'regular' }}">
                                {{ ucfirst($customer->customer_type ?? 'Regular') }}
                            </span>
                        </div>
                        
                        <div class="ultra-customer-metrics">
                            <div class="ultra-metric-item">
                                <div class="ultra-metric-value">€{{ number_format($customer->lifetime_value ?? 0, 0) }}</div>
                                <div class="ultra-metric-label">Lifetime Value</div>
                            </div>
                            <div class="ultra-metric-item">
                                <div class="ultra-metric-value">{{ $customer->appointment_count ?? 0 }}</div>
                                <div class="ultra-metric-label">Visits</div>
                            </div>
                            <div class="ultra-metric-item">
                                <div class="ultra-metric-value">{{ $customer->average_spend ?? 0 }}€</div>
                                <div class="ultra-metric-label">Avg Spend</div>
                            </div>
                            <div class="ultra-metric-item">
                                <div class="ultra-metric-value">{{ $customer->loyalty_score ?? 98 }}%</div>
                                <div class="ultra-metric-label">Loyalty</div>
                            </div>
                        </div>
                        
                        <div class="ultra-customer-timeline">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Activity</h4>
                            <div class="space-y-2">
                                <div class="ultra-activity-item">
                                    <div class="ultra-activity-icon">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="ultra-activity-content">
                                        <div class="ultra-activity-title">Appointment completed - Haircut (€65)</div>
                                        <div class="ultra-activity-time">2 days ago</div>
                                    </div>
                                </div>
                                @if($customer->next_appointment)
                                <div class="ultra-activity-item">
                                    <div class="ultra-activity-icon" style="background: #DBEAFE;">
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ultra-activity-content">
                                        <div class="ultra-activity-title">Next appointment scheduled</div>
                                        <div class="ultra-activity-time">{{ $customer->next_appointment }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="ultra-customer-actions">
                            <button class="ultra-customer-action" onclick="viewCustomerProfile({{ $customer->id }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View Profile
                            </button>
                            <button class="ultra-customer-action" onclick="bookAppointment({{ $customer->id }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Book
                            </button>
                            <button class="ultra-customer-action" onclick="contactCustomer({{ $customer->id }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Contact
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No customers found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding a new customer.</p>
                        <button class="ultra-customer-action primary mt-4" onclick="openNewCustomerModal()">
                            Add Customer
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
        
        {{-- Segments View --}}
        <div id="segments-view" class="view-content hidden">
            <div class="ultra-segments-container">
                <div class="ultra-segment-card" onclick="filterBySegment('vip')">
                    <div class="ultra-segment-header">
                        <div>
                            <div class="ultra-segment-title">⭐ VIP Customers</div>
                            <p class="text-sm text-gray-600 mt-1">High value, frequent visitors</p>
                        </div>
                        <div class="ultra-segment-count">89</div>
                    </div>
                    <div class="mt-4">
                        <div class="text-sm text-gray-500">Criteria:</div>
                        <ul class="text-sm mt-1 space-y-1">
                            <li>• Lifetime value > €1,000</li>
                            <li>• Visit frequency > 1/month</li>
                            <li>• Member for > 1 year</li>
                        </ul>
                    </div>
                </div>
                
                <div class="ultra-segment-card" onclick="filterBySegment('new')">
                    <div class="ultra-segment-header">
                        <div>
                            <div class="ultra-segment-title">🆕 New Customers</div>
                            <p class="text-sm text-gray-600 mt-1">Recently joined</p>
                        </div>
                        <div class="ultra-segment-count">142</div>
                    </div>
                    <div class="mt-4">
                        <div class="text-sm text-gray-500">Criteria:</div>
                        <ul class="text-sm mt-1 space-y-1">
                            <li>• Joined < 30 days ago</li>
                            <li>• First appointment pending/completed</li>
                        </ul>
                    </div>
                </div>
                
                <div class="ultra-segment-card" onclick="filterBySegment('at-risk')">
                    <div class="ultra-segment-header">
                        <div>
                            <div class="ultra-segment-title">⚠️ At Risk</div>
                            <p class="text-sm text-gray-600 mt-1">Need re-engagement</p>
                        </div>
                        <div class="ultra-segment-count">23</div>
                    </div>
                    <div class="mt-4">
                        <div class="text-sm text-gray-500">Criteria:</div>
                        <ul class="text-sm mt-1 space-y-1">
                            <li>• No visit in 90+ days</li>
                            <li>• Declining visit frequency</li>
                            <li>• Cancelled appointments</li>
                        </ul>
                    </div>
                </div>
                
                <div class="ultra-segment-card" onclick="filterBySegment('birthday')">
                    <div class="ultra-segment-header">
                        <div>
                            <div class="ultra-segment-title">🎂 Birthday This Month</div>
                            <p class="text-sm text-gray-600 mt-1">Special occasion</p>
                        </div>
                        <div class="ultra-segment-count">34</div>
                    </div>
                    <div class="mt-4">
                        <div class="text-sm text-gray-500">Action:</div>
                        <button class="ultra-customer-action mt-2">
                            Send Birthday Wishes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Map View --}}
        <div id="map-view" class="view-content hidden">
            <div class="ultra-customer-map">
                <div class="ultra-map-overlay">
                    <h3 class="font-semibold mb-2">Customer Distribution</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>📍 Berlin-Mitte: 342</div>
                        <div>📍 Prenzlauer Berg: 287</div>
                        <div>📍 Kreuzberg: 198</div>
                        <div>📍 Charlottenburg: 156</div>
                    </div>
                </div>
                <!-- Map would be rendered here -->
            </div>
        </div>
    </div>
    
    {{-- Pagination --}}
    @if(method_exists($this, 'getTableRecords') && $this->getTableRecords()->hasPages())
        <div class="mt-6">
            {{ $this->getTableRecords()->links() }}
        </div>
    @endif
    
    @push('scripts')
    <script>
        // View Switcher
        function switchView(view) {
            // Hide all views
            document.querySelectorAll('.view-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.view-tab').forEach(el => el.classList.remove('active'));
            
            // Show selected view
            document.getElementById(view + '-view').classList.remove('hidden');
            document.querySelector(`[data-view="${view}"]`).classList.add('active');
            
            // Save preference
            localStorage.setItem('preferred-customer-view', view);
        }
        
        // Smart Search
        let searchTimeout;
        function performSmartSearch(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Implement smart search logic
                console.log('Searching for:', query);
                // Could use Livewire here: @this.searchCustomers(query)
            }, 300);
        }
        
        // Customer Actions
        function viewCustomerProfile(id) {
            window.location.href = `/admin/customers/${id}`;
        }
        
        function bookAppointment(customerId) {
            // Open appointment booking modal
            alert('Book appointment for customer ' + customerId);
        }
        
        function contactCustomer(customerId) {
            // Open communication modal
            alert('Contact customer ' + customerId);
        }
        
        function openNewCustomerModal() {
            // Open new customer modal
            alert('New customer modal would open here');
        }
        
        function filterBySegment(segment) {
            // Apply segment filter
            alert('Filter by segment: ' + segment);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Restore preferred view
            const preferredView = localStorage.getItem('preferred-customer-view') || 'all';
            switchView(preferredView);
        });
    </script>
    
    <style>
        .view-tab {
            padding: 0.75rem 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6B7280;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .view-tab:hover {
            color: #3B82F6;
        }
        
        .view-tab.active {
            color: #3B82F6;
            border-bottom-color: #3B82F6;
        }
    </style>
    @endpush
</x-filament-panels::page>