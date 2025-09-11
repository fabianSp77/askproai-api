<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Get the complete navigation structure with mega-menu support
     */
    public function getNavigation(): array
    {
        return Cache::remember('navigation.' . Auth::id(), 3600, function () {
            return [
                'main' => $this->getMainNavigation(),
                'mega' => $this->getMegaMenuStructure(),
                'mobile' => $this->getMobileNavigation(),
                'user' => $this->getUserNavigation(),
                'search' => $this->getSearchableItems(),
            ];
        });
    }

    /**
     * Main navigation items (top-level)
     */
    private function getMainNavigation(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'url' => '/admin',
                'icon' => 'heroicon-o-home',
                'badge' => null,
                'active' => request()->is('admin'),
            ],
            [
                'id' => 'products',
                'label' => 'Management',
                'url' => '#',
                'icon' => 'heroicon-o-cube',
                'hasMega' => true,
                'megaContent' => 'products',
            ],
        ];
    }

    /**
     * Mega menu structure (Stripe-style)
     */
    private function getMegaMenuStructure(): array
    {
        return [
            'products' => [
                'columns' => [
                    [
                        'title' => 'Call Management',
                        'items' => [
                            [
                                'icon' => 'heroicon-o-phone',
                                'label' => 'Calls',
                                'description' => 'Track and manage all calls',
                                'url' => '/admin/calls',
                            ],
                            [
                                'icon' => 'heroicon-o-calendar',
                                'label' => 'Appointments',
                                'description' => 'Schedule and manage appointments',
                                'url' => '/admin/appointments',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Customer Relations',
                        'items' => [
                            [
                                'icon' => 'heroicon-o-users',
                                'label' => 'Customers',
                                'description' => 'Manage customer database',
                                'url' => '/admin/customers',
                            ],
                            [
                                'icon' => 'heroicon-o-building-office',
                                'label' => 'Companies',
                                'description' => 'Company management',
                                'url' => '/admin/companies',
                            ],
                            [
                                'icon' => 'heroicon-o-user-group',
                                'label' => 'Staff',
                                'description' => 'Team member management',
                                'url' => '/admin/staff',
                            ],
                        ],
                    ],
                    [
                        'title' => 'System Management',
                        'items' => [
                            [
                                'icon' => 'heroicon-o-map-pin',
                                'label' => 'Branches',
                                'description' => 'Manage business locations',
                                'url' => '/admin/branches',
                            ],
                            [
                                'icon' => 'heroicon-o-user-circle',
                                'label' => 'Users',
                                'description' => 'System user management',
                                'url' => '/admin/users',
                            ],
                            [
                                'icon' => 'heroicon-o-cog-6-tooth',
                                'label' => 'Integrations',
                                'description' => 'Third-party connections',
                                'url' => '/admin/integrations',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Configuration',
                        'items' => [
                            [
                                'icon' => 'heroicon-o-clipboard-document-list',
                                'label' => 'Services',
                                'description' => 'Service catalog',
                                'url' => '/admin/services',
                            ],
                            [
                                'icon' => 'heroicon-o-clock',
                                'label' => 'Working Hours',
                                'description' => 'Schedule configuration',
                                'url' => '/admin/working-hours',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Mobile-optimized navigation
     */
    private function getMobileNavigation(): array
    {
        return [
            'primary' => [
                ['label' => 'Dashboard', 'url' => '/admin', 'icon' => 'heroicon-o-home'],
                ['label' => 'Calls', 'url' => '/admin/calls', 'icon' => 'heroicon-o-phone'],
                ['label' => 'Customers', 'url' => '/admin/customers', 'icon' => 'heroicon-o-users'],
                ['label' => 'Appointments', 'url' => '/admin/appointments', 'icon' => 'heroicon-o-calendar'],
            ],
            'secondary' => [
                ['label' => 'Companies', 'url' => '/admin/companies', 'icon' => 'heroicon-o-building-office'],
                ['label' => 'Staff', 'url' => '/admin/staff', 'icon' => 'heroicon-o-user-group'],
                ['label' => 'Branches', 'url' => '/admin/branches', 'icon' => 'heroicon-o-map-pin'],
                ['label' => 'Users', 'url' => '/admin/users', 'icon' => 'heroicon-o-user-circle'],
                ['label' => 'Services', 'url' => '/admin/services', 'icon' => 'heroicon-o-clipboard-document-list'],
                ['label' => 'Integrations', 'url' => '/admin/integrations', 'icon' => 'heroicon-o-cog-6-tooth'],
                ['label' => 'Working Hours', 'url' => '/admin/working-hours', 'icon' => 'heroicon-o-clock'],
            ],
        ];
    }

    /**
     * User-specific navigation
     */
    private function getUserNavigation(): array
    {
        $user = Auth::user();
        
        return [
            'profile' => [
                'name' => $user->name ?? 'User',
                'email' => $user->email ?? '',
                'avatar' => $user->avatar_url ?? null,
            ],
            'menu' => [
                ['label' => 'Profile', 'url' => '/admin/profile', 'icon' => 'heroicon-o-user'],
                ['label' => 'Settings', 'url' => '/admin/settings', 'icon' => 'heroicon-o-cog'],
                'divider',
                ['label' => 'Help', 'url' => '/admin/help', 'icon' => 'heroicon-o-question-mark-circle'],
                ['label' => 'Sign out', 'url' => '/logout', 'icon' => 'heroicon-o-arrow-right-on-rectangle'],
            ],
        ];
    }

    /**
     * Get searchable items for command palette
     */
    private function getSearchableItems(): array
    {
        return collect($this->getMegaMenuStructure())
            ->flatMap(function ($section) {
                return collect($section['columns'])->flatMap(function ($column) {
                    return collect($column['items'])->map(function ($item) use ($column) {
                        return [
                            'id' => \Str::slug($item['label']),
                            'label' => $item['label'],
                            'description' => $item['description'],
                            'url' => $item['url'],
                            'category' => $column['title'],
                            'icon' => $item['icon'] ?? 'heroicon-o-document',
                            'keywords' => $this->generateKeywords($item),
                        ];
                    });
                });
            })
            ->toArray();
    }

    /**
     * Generate search keywords for an item
     */
    private function generateKeywords(array $item): array
    {
        $keywords = [];
        $keywords[] = strtolower($item['label']);
        $keywords[] = strtolower($item['description'] ?? '');
        
        // Add common variations
        if (str_contains($item['label'], 'Customer')) {
            $keywords[] = 'client';
            $keywords[] = 'contact';
        }
        
        if (str_contains($item['label'], 'Call')) {
            $keywords[] = 'phone';
            $keywords[] = 'voice';
        }
        
        return array_filter($keywords);
    }

    /**
     * Get recently accessed items
     */
    public function getRecentItems(): array
    {
        $userId = Auth::id();
        
        return Cache::get("recent_items.{$userId}", []);
    }

    /**
     * Track item access
     */
    public function trackAccess(string $itemId): void
    {
        $userId = Auth::id();
        $recent = $this->getRecentItems();
        
        // Add to front, remove duplicates, limit to 5
        array_unshift($recent, $itemId);
        $recent = array_unique($recent);
        $recent = array_slice($recent, 0, 5);
        
        Cache::put("recent_items.{$userId}", $recent, 86400); // 24 hours
    }
}