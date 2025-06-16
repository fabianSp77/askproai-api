<x-mobile.layout>
    <x-slot name="pageTitle">Customers</x-slot>
    
    <x-slot name="headerRight">
        <button class="touch-target p-2" onclick="toggleSearch()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
    </x-slot>
    
    <div class="mobile-content-container">
        <!-- Search Bar -->
        <div id="searchBar" class="p-4 bg-white border-b border-gray-200 hidden">
            <div class="relative">
                <input type="text" 
                       class="mobile-input pl-10 pr-10" 
                       placeholder="Search customers..."
                       id="customerSearch"
                       autocomplete="off">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <button class="absolute right-3 top-1/2 transform -translate-y-1/2" onclick="clearSearch()">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Quick Filters -->
        <div class="p-4 pb-0">
            <div class="mobile-scroll-x flex gap-2 -mx-4 px-4">
                <button class="px-4 py-2 bg-amber-500 text-white rounded-full whitespace-nowrap text-sm font-medium">
                    All Customers
                </button>
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full whitespace-nowrap text-sm font-medium">
                    Recent
                </button>
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full whitespace-nowrap text-sm font-medium">
                    VIP
                </button>
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full whitespace-nowrap text-sm font-medium">
                    Birthday This Month
                </button>
            </div>
        </div>
        
        <!-- Customer Stats -->
        <div class="p-4">
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-lg p-3 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-gray-900">156</p>
                    <p class="text-xs text-gray-600">Total</p>
                </div>
                <div class="bg-white rounded-lg p-3 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-green-600">12</p>
                    <p class="text-xs text-gray-600">New This Month</p>
                </div>
                <div class="bg-white rounded-lg p-3 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-amber-600">8</p>
                    <p class="text-xs text-gray-600">Birthdays</p>
                </div>
            </div>
        </div>
        
        <!-- Customer List -->
        <div class="px-4 pb-4">
            <div class="bg-white rounded-lg overflow-hidden">
                <!-- Alphabet Jump List -->
                <div class="flex justify-between px-4 py-2 bg-gray-50 border-b border-gray-200 text-xs font-medium text-gray-600">
                    @foreach(range('A', 'Z') as $letter)
                    <button class="touch-target-min" onclick="jumpToLetter('{{ $letter }}')">{{ $letter }}</button>
                    @endforeach
                </div>
                
                <!-- Customer Items -->
                <div class="divide-y divide-gray-200">
                    @foreach(['A', 'B', 'C'] as $letter)
                    <!-- Letter Group -->
                    <div>
                        <div class="px-4 py-2 bg-gray-50 text-sm font-semibold text-gray-700" id="letter-{{ $letter }}">
                            {{ $letter }}
                        </div>
                        @foreach(range(1, rand(2, 4)) as $i)
                        <div class="mobile-list-item px-4" onclick="viewCustomer({{ $i }})">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-amber-600 font-semibold">{{ $letter }}{{ substr(fake()->firstName(), 1, 1) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900">{{ fake()->name() }}</p>
                                    <p class="text-sm text-gray-500">{{ fake()->phoneNumber() }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">{{ rand(1, 20) }} visits</p>
                                    @if(rand(0, 1))
                                    <span class="inline-block w-2 h-2 bg-green-400 rounded-full mt-1"></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Search Results (Hidden by default) -->
        <div id="searchResults" class="hidden px-4 pb-4">
            <p class="text-sm text-gray-600 mb-3">Search results for "<span id="searchQuery"></span>"</p>
            <div class="space-y-2">
                <!-- Results will be populated by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Customer Quick View Modal -->
    <x-slot name="modals">
        <div class="mobile-modal" id="customerModal">
            <div class="mobile-modal-handle"></div>
            
            <div class="text-center mb-4">
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-amber-600 font-semibold text-2xl" id="customerInitials">JD</span>
                </div>
                <h3 class="text-xl font-semibold" id="customerName">John Doe</h3>
                <p class="text-gray-600" id="customerPhone">+1 234 567 8900</p>
            </div>
            
            <div class="space-y-4">
                <!-- Contact Actions -->
                <div class="flex gap-3">
                    <button class="mobile-button mobile-button-primary flex-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Call
                    </button>
                    <button class="mobile-button mobile-button-secondary flex-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Message
                    </button>
                </div>
                
                <!-- Customer Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Customer Information</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Visits</span>
                            <span class="font-medium">23</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Visit</span>
                            <span class="font-medium">3 days ago</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Average Spend</span>
                            <span class="font-medium">€45</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Birthday</span>
                            <span class="font-medium">May 15</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="space-y-2">
                    <button class="w-full mobile-button mobile-button-primary">
                        Book New Appointment
                    </button>
                    <button class="w-full mobile-button mobile-button-secondary">
                        View Full Profile
                    </button>
                </div>
            </div>
        </div>
        
        <div class="mobile-modal-backdrop" id="modalBackdrop" onclick="closeCustomerModal()"></div>
    </x-slot>
    
    <!-- Floating Action Button -->
    <x-slot name="fab">
        <button class="fab" onclick="openNewCustomerModal()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
        </button>
    </x-slot>
    
    <x-slot name="scripts">
        <script>
            let searchOpen = false;
            
            function toggleSearch() {
                searchOpen = !searchOpen;
                const searchBar = document.getElementById('searchBar');
                
                if (searchOpen) {
                    searchBar.classList.remove('hidden');
                    document.getElementById('customerSearch').focus();
                } else {
                    searchBar.classList.add('hidden');
                    clearSearch();
                }
            }
            
            function clearSearch() {
                document.getElementById('customerSearch').value = '';
                document.getElementById('searchResults').classList.add('hidden');
                // Show normal customer list
            }
            
            // Live search
            let searchTimeout;
            document.getElementById('customerSearch').addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const query = e.target.value;
                
                if (query.length > 0) {
                    searchTimeout = setTimeout(() => {
                        performSearch(query);
                    }, 300);
                } else {
                    clearSearch();
                }
            });
            
            function performSearch(query) {
                // In real app, this would make an API call
                document.getElementById('searchQuery').textContent = query;
                document.getElementById('searchResults').classList.remove('hidden');
                
                // Simulate search results
                const results = document.getElementById('searchResults').querySelector('.space-y-2');
                results.innerHTML = `
                    <div class="mobile-card p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                                <span class="text-amber-600 font-semibold">JD</span>
                            </div>
                            <div>
                                <p class="font-medium">John Doe</p>
                                <p class="text-sm text-gray-500">+1 234 567 8900</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            function jumpToLetter(letter) {
                const element = document.getElementById(`letter-${letter}`);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            
            function viewCustomer(id) {
                // Open customer modal
                document.getElementById('customerModal').classList.add('open');
                document.getElementById('modalBackdrop').classList.add('visible');
                document.body.classList.add('modal-open');
            }
            
            function closeCustomerModal() {
                document.getElementById('customerModal').classList.remove('open');
                document.getElementById('modalBackdrop').classList.remove('visible');
                document.body.classList.remove('modal-open');
            }
            
            function openNewCustomerModal() {
                console.log('Opening new customer modal');
            }
            
            // Swipe down to close modal
            let modalStartY = 0;
            const modal = document.getElementById('customerModal');
            const modalHandle = modal.querySelector('.mobile-modal-handle');
            
            modalHandle.addEventListener('touchstart', function(e) {
                modalStartY = e.touches[0].clientY;
            });
            
            modalHandle.addEventListener('touchmove', function(e) {
                const currentY = e.touches[0].clientY;
                const diff = currentY - modalStartY;
                
                if (diff > 0) {
                    modal.style.transform = `translateY(${diff}px)`;
                }
            });
            
            modalHandle.addEventListener('touchend', function(e) {
                const currentY = e.changedTouches[0].clientY;
                const diff = currentY - modalStartY;
                
                if (diff > 100) {
                    closeCustomerModal();
                }
                
                modal.style.transform = '';
            });
        </script>
    </x-slot>
</x-mobile.layout>