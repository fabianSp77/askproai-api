<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TutorialService
{
    /**
     * Default tutorials that should be seeded
     */
    protected array $defaultTutorials = [
        // Dashboard tutorials
        [
            'key' => 'dashboard_welcome',
            'title' => 'Willkommen im Dashboard',
            'description' => 'Hier sehen Sie eine Übersicht Ihrer wichtigsten Kennzahlen',
            'type' => 'tooltip',
            'target_selector' => '.fi-da-stats-overview',
            'page_route' => '/admin',
            'order_index' => 1,
        ],
        [
            'key' => 'dashboard_navigation',
            'title' => 'Navigation',
            'description' => 'Nutzen Sie das Seitenmenü, um zu verschiedenen Bereichen zu navigieren',
            'type' => 'tooltip',
            'target_selector' => '.fi-sidebar',
            'page_route' => '/admin',
            'order_index' => 2,
        ],
        
        // Appointment tutorials
        [
            'key' => 'appointments_list',
            'title' => 'Terminübersicht',
            'description' => 'Hier sehen Sie alle gebuchten Termine. Klicken Sie auf einen Termin für Details.',
            'type' => 'tooltip',
            'target_selector' => '.fi-ta-table',
            'page_route' => '/admin/appointments',
            'order_index' => 1,
        ],
        [
            'key' => 'appointments_create',
            'title' => 'Neuen Termin erstellen',
            'description' => 'Klicken Sie hier, um manuell einen neuen Termin zu erstellen',
            'type' => 'tooltip',
            'target_selector' => '.fi-btn-create',
            'page_route' => '/admin/appointments',
            'order_index' => 2,
        ],
        
        // Staff tutorials
        [
            'key' => 'staff_manage',
            'title' => 'Mitarbeiter verwalten',
            'description' => 'Fügen Sie neue Mitarbeiter hinzu oder bearbeiten Sie bestehende',
            'type' => 'tour',
            'page_route' => '/admin/staff',
            'order_index' => 1,
            'config' => [
                'steps' => [
                    [
                        'target' => '.fi-btn-create',
                        'content' => 'Klicken Sie hier, um einen neuen Mitarbeiter hinzuzufügen',
                    ],
                    [
                        'target' => '.fi-ta-actions',
                        'content' => 'Nutzen Sie die Aktionen, um Mitarbeiter zu bearbeiten oder zu löschen',
                    ],
                    [
                        'target' => '.fi-ta-filters',
                        'content' => 'Filtern Sie die Liste nach verschiedenen Kriterien',
                    ],
                ],
            ],
        ],
        
        // Service tutorials
        [
            'key' => 'services_setup',
            'title' => 'Dienstleistungen einrichten',
            'description' => 'Definieren Sie die Dienstleistungen, die gebucht werden können',
            'type' => 'video',
            'page_route' => '/admin/services',
            'order_index' => 1,
            'config' => [
                'video_url' => '/tutorials/services-setup.mp4',
                'duration' => '3:45',
            ],
        ],
        
        // Settings tutorials
        [
            'key' => 'settings_company',
            'title' => 'Unternehmenseinstellungen',
            'description' => 'Passen Sie Ihre Unternehmensdaten und Präferenzen an',
            'type' => 'article',
            'page_route' => '/admin/companies/*',
            'order_index' => 1,
            'config' => [
                'article_url' => '/docs/company-settings',
            ],
        ],
    ];

    /**
     * Initialize default tutorials
     */
    public function initializeDefaultTutorials(): void
    {
        foreach ($this->defaultTutorials as $tutorial) {
            DB::table('onboarding_tutorials')->insertOrIgnore([
                'key' => $tutorial['key'],
                'title' => $tutorial['title'],
                'description' => $tutorial['description'],
                'type' => $tutorial['type'],
                'config' => isset($tutorial['config']) ? json_encode($tutorial['config']) : null,
                'target_selector' => $tutorial['target_selector'] ?? null,
                'page_route' => $tutorial['page_route'],
                'order_index' => $tutorial['order_index'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get tutorials for current page
     */
    public function getTutorialsForCurrentPage(string $currentRoute, User $user): Collection
    {
        // Get all active tutorials that match the current route
        // Sanitize currentRoute to prevent SQL injection in LIKE patterns
        $sanitizedRoute = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $currentRoute);
        
        $tutorials = DB::table('onboarding_tutorials')
            ->where('is_active', true)
            ->where(function ($query) use ($currentRoute, $sanitizedRoute) {
                $query->where('page_route', $currentRoute)
                    ->orWhere('page_route', 'LIKE', '%*%')
                    ->whereRaw("? LIKE REPLACE(page_route, '*', '%')", [$sanitizedRoute]);
            })
            ->orderBy('order_index')
            ->get();

        // Get user's progress
        $userProgress = DB::table('user_tutorial_progress')
            ->where('user_id', $user->id)
            ->pluck('is_completed', 'tutorial_id');

        // Combine tutorials with user progress
        return $tutorials->map(function ($tutorial) use ($userProgress) {
            $tutorial->is_completed = $userProgress[$tutorial->id] ?? false;
            $tutorial->config = json_decode($tutorial->config, true);
            return $tutorial;
        });
    }

    /**
     * Mark tutorial as viewed
     */
    public function markAsViewed(User $user, int $tutorialId): void
    {
        DB::table('user_tutorial_progress')->insertOrIgnore([
            'user_id' => $user->id,
            'tutorial_id' => $tutorialId,
            'is_viewed' => true,
            'viewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Mark tutorial as completed
     */
    public function markAsCompleted(User $user, int $tutorialId): void
    {
        DB::table('user_tutorial_progress')
            ->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'tutorial_id' => $tutorialId,
                ],
                [
                    'is_viewed' => true,
                    'is_completed' => true,
                    'viewed_at' => DB::raw('IFNULL(viewed_at, NOW())'),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]
            );
    }

    /**
     * Get user's tutorial progress summary
     */
    public function getUserProgress(User $user): array
    {
        $totalTutorials = DB::table('onboarding_tutorials')
            ->where('is_active', true)
            ->count();

        $completedTutorials = DB::table('user_tutorial_progress')
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        $viewedTutorials = DB::table('user_tutorial_progress')
            ->where('user_id', $user->id)
            ->where('is_viewed', true)
            ->count();

        return [
            'total' => $totalTutorials,
            'completed' => $completedTutorials,
            'viewed' => $viewedTutorials,
            'completion_percentage' => $totalTutorials > 0 
                ? round(($completedTutorials / $totalTutorials) * 100) 
                : 0,
        ];
    }

    /**
     * Reset user's tutorial progress
     */
    public function resetProgress(User $user): void
    {
        DB::table('user_tutorial_progress')
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Get next unviewed tutorial for user
     */
    public function getNextTutorial(User $user, string $currentRoute): ?object
    {
        $viewedTutorialIds = DB::table('user_tutorial_progress')
            ->where('user_id', $user->id)
            ->where('is_viewed', true)
            ->pluck('tutorial_id');

        // Sanitize currentRoute to prevent SQL injection in LIKE patterns
        $sanitizedRoute = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $currentRoute);
        
        return DB::table('onboarding_tutorials')
            ->where('is_active', true)
            ->whereNotIn('id', $viewedTutorialIds)
            ->where(function ($query) use ($currentRoute, $sanitizedRoute) {
                $query->where('page_route', $currentRoute)
                    ->orWhere('page_route', 'LIKE', '%*%')
                    ->whereRaw("? LIKE REPLACE(page_route, '*', '%')", [$sanitizedRoute]);
            })
            ->orderBy('order_index')
            ->first();
    }
}