<x-mobile.layout>
    <x-slot name="pageTitle">Profile</x-slot>
    
    <x-slot name="headerRight">
        <button class="touch-target p-2" onclick="openSettings()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </button>
    </x-slot>
    
    <div class="mobile-content-container">
        <!-- Profile Header -->
        <div class="bg-amber-500 px-4 pt-8 pb-16">
            <div class="text-center text-white">
                <div class="w-24 h-24 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-amber-500 text-3xl font-bold">{{ substr($user->name ?? 'U', 0, 1) }}</span>
                </div>
                <h2 class="text-2xl font-semibold">{{ $user->name ?? 'User' }}</h2>
                <p class="text-amber-100">{{ $user->email ?? '' }}</p>
            </div>
        </div>
        
        <!-- Company Info Card -->
        <div class="px-4 -mt-8">
            <div class="mobile-card bg-white shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Company</h3>
                    <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Active</span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name</span>
                        <span class="font-medium">{{ $company->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Plan</span>
                        <span class="font-medium">Professional</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Members</span>
                        <span class="font-medium">{{ $company->users()->count() ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="p-4">
            <h3 class="text-lg font-semibold mb-3">Your Activity</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-lg p-4 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-gray-900">142</p>
                    <p class="text-sm text-gray-600">Appointments</p>
                    <p class="text-xs text-gray-500 mt-1">This month</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-gray-900">89%</p>
                    <p class="text-sm text-gray-600">Completion</p>
                    <p class="text-xs text-gray-500 mt-1">Rate</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-gray-900">4.8</p>
                    <p class="text-sm text-gray-600">Rating</p>
                    <p class="text-xs text-gray-500 mt-1">From customers</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-gray-900">32</p>
                    <p class="text-sm text-gray-600">Hours</p>
                    <p class="text-xs text-gray-500 mt-1">This week</p>
                </div>
            </div>
        </div>
        
        <!-- Settings Menu -->
        <div class="p-4">
            <h3 class="text-lg font-semibold mb-3">Settings</h3>
            <div class="bg-white rounded-lg overflow-hidden">
                <a href="/mobile/settings/notifications" class="mobile-list-item border-b border-gray-200">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">Notifications</p>
                            <p class="text-sm text-gray-500">Push, email, SMS settings</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
                
                <a href="/mobile/settings/availability" class="mobile-list-item border-b border-gray-200">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">Working Hours</p>
                            <p class="text-sm text-gray-500">Set your availability</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
                
                <a href="/mobile/settings/security" class="mobile-list-item border-b border-gray-200">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">Security</p>
                            <p class="text-sm text-gray-500">Password, 2FA, sessions</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
                
                <a href="/mobile/settings/help" class="mobile-list-item">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">Help & Support</p>
                            <p class="text-sm text-gray-500">FAQs, contact support</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- App Info -->
        <div class="p-4 pb-8">
            <button class="w-full mobile-button mobile-button-secondary" onclick="logout()">
                Sign Out
            </button>
            
            <div class="text-center mt-6 text-sm text-gray-500">
                <p>AskProAI v1.0.0</p>
                <p class="mt-1">© 2024 AskProAI. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <x-slot name="scripts">
        <script>
            function openSettings() {
                console.log('Opening settings');
            }
            
            function logout() {
                if (confirm('Are you sure you want to sign out?')) {
                    // Submit logout form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/logout';
                    
                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = document.querySelector('meta[name="csrf-token"]')?.content;
                    
                    form.appendChild(token);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
    </x-slot>
</x-mobile.layout>