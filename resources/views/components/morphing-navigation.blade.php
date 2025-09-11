{{-- Stripe-Style Morphing Navigation Component --}}
@php
    $user = Auth::user();
    $navigation = [
        'main' => [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'url' => '/admin',
                'icon' => 'heroicon-o-home',
            ],
            [
                'id' => 'operations',
                'label' => 'Operations',
                'url' => '#',
                'hasDropdown' => true,
                'dropdownId' => 'operations-dropdown',
            ],
            [
                'id' => 'management',
                'label' => 'Management',
                'url' => '#',
                'hasDropdown' => true,
                'dropdownId' => 'management-dropdown',
            ],
            [
                'id' => 'system',
                'label' => 'System',
                'url' => '#',
                'hasDropdown' => true,
                'dropdownId' => 'system-dropdown',
            ],
        ],
        'dropdowns' => [
            'operations' => [
                'columns' => [
                    [
                        'title' => 'Call Management',
                        'items' => [
                            ['icon' => 'phone', 'label' => 'Active Calls', 'description' => 'Monitor ongoing calls', 'url' => '/admin/calls'],
                            ['icon' => 'phone-incoming', 'label' => 'Call History', 'description' => 'View past interactions', 'url' => '/admin/calls?filter=history'],
                            ['icon' => 'voicemail', 'label' => 'Voicemails', 'description' => 'Manage voice messages', 'url' => '/admin/voicemails'],
                        ],
                    ],
                    [
                        'title' => 'Scheduling',
                        'items' => [
                            ['icon' => 'calendar', 'label' => 'Appointments', 'description' => 'Manage bookings', 'url' => '/admin/appointments'],
                            ['icon' => 'clock', 'label' => 'Working Hours', 'description' => 'Set availability', 'url' => '/admin/working-hours'],
                            ['icon' => 'users', 'label' => 'Staff Schedule', 'description' => 'Team calendars', 'url' => '/admin/staff-schedule'],
                        ],
                    ],
                ],
                'featured' => [
                    'title' => 'Quick Actions',
                    'items' => [
                        ['label' => 'New Appointment', 'url' => '/admin/appointments/create', 'badge' => 'CMD+N'],
                        ['label' => 'Today\'s Schedule', 'url' => '/admin/appointments?date=today', 'badge' => 'Popular'],
                    ],
                ],
            ],
            'management' => [
                'columns' => [
                    [
                        'title' => 'Customer Relations',
                        'items' => [
                            ['icon' => 'users', 'label' => 'Customers', 'description' => 'Customer database', 'url' => '/admin/customers'],
                            ['icon' => 'building', 'label' => 'Companies', 'description' => 'Business accounts', 'url' => '/admin/companies'],
                            ['icon' => 'user-group', 'label' => 'Staff', 'description' => 'Team members', 'url' => '/admin/staff'],
                        ],
                    ],
                    [
                        'title' => 'Business Settings',
                        'items' => [
                            ['icon' => 'map-pin', 'label' => 'Branches', 'description' => 'Locations', 'url' => '/admin/branches'],
                            ['icon' => 'briefcase', 'label' => 'Services', 'description' => 'Service catalog', 'url' => '/admin/services'],
                            ['icon' => 'credit-card', 'label' => 'Billing', 'description' => 'Payment settings', 'url' => '/admin/billing'],
                        ],
                    ],
                ],
            ],
            'system' => [
                'columns' => [
                    [
                        'title' => 'Configuration',
                        'items' => [
                            ['icon' => 'cog', 'label' => 'Settings', 'description' => 'System config', 'url' => '/admin/settings'],
                            ['icon' => 'puzzle', 'label' => 'Integrations', 'description' => 'Third-party apps', 'url' => '/admin/integrations'],
                            ['icon' => 'key', 'label' => 'API Keys', 'description' => 'Developer access', 'url' => '/admin/api-keys'],
                        ],
                    ],
                    [
                        'title' => 'Security',
                        'items' => [
                            ['icon' => 'shield-check', 'label' => 'Users', 'description' => 'User management', 'url' => '/admin/users'],
                            ['icon' => 'lock-closed', 'label' => 'Roles', 'description' => 'Permissions', 'url' => '/admin/roles'],
                            ['icon' => 'clipboard-list', 'label' => 'Audit Log', 'description' => 'Activity tracking', 'url' => '/admin/audit'],
                        ],
                    ],
                ],
            ],
        ],
    ];
@endphp

<header class="morph-nav" x-data="morphingNavigation()" x-init="init()">
    {{-- Main Navigation Bar --}}
    <nav class="morph-nav-bar" role="navigation" aria-label="Main navigation">
        <div class="morph-nav-container">
            {{-- Logo --}}
            <a href="/admin" class="morph-nav-logo">
                <span class="font-bold text-xl">{{ config('app.name', 'AskProAI') }}</span>
            </a>

            {{-- Desktop Navigation --}}
            <ul class="morph-nav-list">
                @foreach($navigation['main'] as $item)
                    <li class="morph-nav-item">
                        @if(isset($item['hasDropdown']) && $item['hasDropdown'])
                            <button 
                                class="morph-nav-link"
                                :class="{ 'active': activeDropdown === '{{ $item['id'] }}' }"
                                @mouseenter="handleHover('{{ $item['id'] }}')"
                                @mouseleave="handleLeave()"
                                @click="toggleDropdown('{{ $item['id'] }}')"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="{{ $item['dropdownId'] }}"
                                data-dropdown="{{ $item['id'] }}"
                            >
                                {{ $item['label'] }}
                                <svg class="morph-nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        @else
                            <a href="{{ $item['url'] }}" class="morph-nav-link">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Right Actions --}}
            <div class="morph-nav-actions">
                {{-- Command Palette Trigger --}}
                <button 
                    @click="openCommandPalette()"
                    class="morph-nav-search"
                    aria-label="Search (Press CMD+K)"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="morph-nav-kbd">⌘K</span>
                </button>

                {{-- User Menu --}}
                @if($user)
                    <button class="morph-nav-user">
                        <span class="morph-nav-avatar">
                            {{ substr($user->name ?? $user->email, 0, 2) }}
                        </span>
                    </button>
                @endif

                {{-- Mobile Menu Toggle --}}
                <button 
                    @click="toggleMobileMenu()"
                    class="morph-nav-hamburger"
                    aria-label="Toggle menu"
                    aria-expanded="false"
                >
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </div>
    </nav>

    {{-- Morphing Dropdown Container --}}
    <div 
        class="morph-dropdown-wrapper"
        x-show="isDropdownOpen"
        x-transition:enter="morph-enter"
        x-transition:enter-start="morph-enter-start"
        x-transition:enter-end="morph-enter-end"
        x-transition:leave="morph-leave"
        x-transition:leave-start="morph-leave-start"
        x-transition:leave-end="morph-leave-end"
        @click.away="closeDropdown()"
    >
        <div class="morph-dropdown-bg" :style="dropdownStyle"></div>
        <div class="morph-dropdown-arrow" :style="arrowStyle"></div>
        
        <div class="morph-dropdown-content" :style="contentStyle">
            @foreach($navigation['dropdowns'] as $key => $dropdown)
                <div 
                    id="{{ $key }}-dropdown"
                    class="morph-dropdown-panel"
                    x-show="activeDropdown === '{{ $key }}'"
                    role="menu"
                >
                    <div class="morph-dropdown-grid">
                        @foreach($dropdown['columns'] as $column)
                            <div class="morph-dropdown-column">
                                <h3 class="morph-dropdown-title">{{ $column['title'] }}</h3>
                                <ul class="morph-dropdown-list">
                                    @foreach($column['items'] as $item)
                                        <li>
                                            <a href="{{ $item['url'] }}" class="morph-dropdown-item" role="menuitem">
                                                @if(isset($item['icon']))
                                                    <span class="morph-dropdown-icon">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                        </svg>
                                                    </span>
                                                @endif
                                                <div class="morph-dropdown-text">
                                                    <span class="morph-dropdown-label">{{ $item['label'] }}</span>
                                                    @if(isset($item['description']))
                                                        <span class="morph-dropdown-desc">{{ $item['description'] }}</span>
                                                    @endif
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                        
                        @if(isset($dropdown['featured']))
                            <div class="morph-dropdown-featured">
                                <h3 class="morph-dropdown-title">{{ $dropdown['featured']['title'] }}</h3>
                                <ul class="morph-dropdown-list">
                                    @foreach($dropdown['featured']['items'] as $item)
                                        <li>
                                            <a href="{{ $item['url'] }}" class="morph-dropdown-featured-item">
                                                {{ $item['label'] }}
                                                @if(isset($item['badge']))
                                                    <span class="morph-dropdown-badge">{{ $item['badge'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Mobile Slide-out Menu --}}
    <div 
        class="morph-mobile-menu"
        :class="{ 'open': isMobileOpen }"
        x-show="isMobileOpen"
        @click.away="closeMobileMenu()"
    >
        <div class="morph-mobile-header">
            <span class="font-bold text-xl">{{ config('app.name') }}</span>
            <button @click="closeMobileMenu()" class="morph-mobile-close">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <nav class="morph-mobile-nav">
            @foreach($navigation['main'] as $item)
                @if(isset($item['hasDropdown']) && $item['hasDropdown'])
                    <div class="morph-mobile-section">
                        <button 
                            @click="toggleMobileSection('{{ $item['id'] }}')"
                            class="morph-mobile-section-header"
                        >
                            {{ $item['label'] }}
                            <svg class="w-5 h-5 transform transition-transform" :class="{ 'rotate-180': mobileSection === '{{ $item['id'] }}' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div 
                            x-show="mobileSection === '{{ $item['id'] }}'"
                            x-transition
                            class="morph-mobile-section-content"
                        >
                            @if(isset($navigation['dropdowns'][$item['id']]))
                                @foreach($navigation['dropdowns'][$item['id']]['columns'] as $column)
                                    <div class="morph-mobile-group">
                                        <h4 class="morph-mobile-group-title">{{ $column['title'] }}</h4>
                                        @foreach($column['items'] as $subitem)
                                            <a href="{{ $subitem['url'] }}" class="morph-mobile-link">
                                                {{ $subitem['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @else
                    <a href="{{ $item['url'] }}" class="morph-mobile-link">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>
    </div>

    {{-- Mobile Overlay --}}
    <div 
        class="morph-mobile-overlay"
        x-show="isMobileOpen"
        x-transition:enter="fade-enter"
        x-transition:leave="fade-leave"
        @click="closeMobileMenu()"
    ></div>
</header>