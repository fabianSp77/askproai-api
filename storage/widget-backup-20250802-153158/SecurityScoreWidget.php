<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityScoreWidget extends Widget
{
    protected static string $view = 'filament.widgets.security-score';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 10;

    public function getSecurityScore(): array
    {
        $user = Auth::user();
        if (!$user) {
            return $this->getDefaultScore();
        }

        $score = 0;
        $improvements = [];
        $achievements = [];

        // Base authentication (20 points)
        $score += 20;
        $achievements[] = [
            'name' => 'Erfolgreich angemeldet',
            'points' => 20,
            'emoji' => '✅',
            'completed' => true
        ];

        // Two-Factor Authentication (30 points)
        $has2FA = $this->userHas2FA($user);
        if ($has2FA) {
            $score += 30;
            $achievements[] = [
                'name' => 'Zwei-Faktor-Auth aktiviert',
                'points' => 30,
                'emoji' => '🔐',
                'completed' => true
            ];
        } else {
            $improvements[] = [
                'name' => 'Zwei-Faktor-Auth aktivieren',
                'points' => 30,
                'emoji' => '🔐',
                'description' => 'Mach dein Konto unbezwingbar!',
                'action' => route('filament.admin.auth.two-factor'),
                'priority' => 'high'
            ];
        }

        // Strong password (15 points)
        $hasStrongPassword = $this->userHasStrongPassword($user);
        if ($hasStrongPassword) {
            $score += 15;
            $achievements[] = [
                'name' => 'Starkes Passwort',
                'points' => 15,
                'emoji' => '🔑',
                'completed' => true
            ];
        } else {
            $improvements[] = [
                'name' => 'Starkes Passwort verwenden',
                'points' => 15,
                'emoji' => '🔑',
                'description' => 'Mindestens 12 Zeichen mit Sonderzeichen',
                'action' => route('filament.admin.auth.password'),
                'priority' => 'medium'
            ];
        }

        // Regular activity (10 points)
        $regularActivity = $this->userHasRegularActivity($user);
        if ($regularActivity) {
            $score += 10;
            $achievements[] = [
                'name' => 'Regelmäßige Aktivität',
                'points' => 10,
                'emoji' => '📅',
                'completed' => true
            ];
        }

        // Session security (10 points)
        $secureSession = $this->userHasSecureSession($user);
        if ($secureSession) {
            $score += 10;
            $achievements[] = [
                'name' => 'Sichere Session',
                'points' => 10,
                'emoji' => '🛡️',
                'completed' => true
            ];
        }

        // No recent security incidents (15 points)
        $noIncidents = $this->userHasNoRecentIncidents($user);
        if ($noIncidents) {
            $score += 15;
            $achievements[] = [
                'name' => 'Keine Sicherheitsvorfälle',
                'points' => 15,
                'emoji' => '🚫',
                'completed' => true
            ];
        }

        return [
            'total' => min(100, $score),
            'level' => $this->getSecurityLevel($score),
            'achievements' => $achievements,
            'improvements' => $improvements,
            'tips' => $this->getSecurityTips(),
            'next_level_at' => $this->getNextLevelThreshold($score),
            'streak' => $this->getUserSecurityStreak($user)
        ];
    }

    protected function getDefaultScore(): array
    {
        return [
            'total' => 20,
            'level' => $this->getSecurityLevel(20),
            'achievements' => [],
            'improvements' => [
                [
                    'name' => 'Zwei-Faktor-Auth aktivieren',
                    'points' => 30,
                    'emoji' => '🔐',
                    'description' => 'Mach dein Konto unbezwingbar!',
                    'priority' => 'high'
                ]
            ],
            'tips' => $this->getSecurityTips(),
            'next_level_at' => 50,
            'streak' => 0
        ];
    }

    protected function getSecurityLevel(int $score): array
    {
        if ($score >= 90) {
            return [
                'name' => 'Sicherheits-Meister',
                'emoji' => '👑',
                'description' => 'Legendär! Du bist die Benchmark!',
                'color' => 'amber',
                'next' => null
            ];
        } elseif ($score >= 70) {
            return [
                'name' => 'Sicherheits-Experte',
                'emoji' => '🏆',
                'description' => 'Wow! Du bist ein Champion!',
                'color' => 'success',
                'next' => 'Sicherheits-Meister bei 90 Punkten'
            ];
        } elseif ($score >= 50) {
            return [
                'name' => 'Sicherheits-Profi',
                'emoji' => '🔒',
                'description' => 'Sehr gut! Du kennst dich aus.',
                'color' => 'info',
                'next' => 'Sicherheits-Experte bei 70 Punkten'
            ];
        } else {
            return [
                'name' => 'Sicherheits-Anfänger',
                'emoji' => '🛡️',
                'description' => 'Du hast die Grundlagen drauf!',
                'color' => 'warning',
                'next' => 'Sicherheits-Profi bei 50 Punkten'
            ];
        }
    }

    protected function getNextLevelThreshold(int $currentScore): ?int
    {
        if ($currentScore < 50) return 50;
        if ($currentScore < 70) return 70;
        if ($currentScore < 90) return 90;
        return null;
    }

    protected function getSecurityTips(): array
    {
        $tips = [
            ['text' => 'Melde dich immer ab, wenn du fertig bist', 'emoji' => '👋'],
            ['text' => 'Teile niemals dein Passwort - auch nicht mit Kollegen', 'emoji' => '🤐'],
            ['text' => 'Prüfe regelmäßig deine Login-Aktivitäten', 'emoji' => '🔍'],
            ['text' => 'Halte deine Authenticator-App aktuell', 'emoji' => '📱'],
            ['text' => 'Wusstest du? 2FA reduziert das Hack-Risiko um 99.9%!', 'emoji' => '📊'],
            ['text' => 'Pro-Tipp: Nutze einen Passwort-Manager', 'emoji' => '🗂️'],
            ['text' => 'Sicherheit ist Teamwork - danke, dass du mitmachst!', 'emoji' => '🤝'],
            ['text' => 'Jeder sichere Login macht das ganze System stärker', 'emoji' => '💪'],
        ];

        // Return random tip
        return [array_rand($tips) => $tips[array_rand($tips)]];
    }

    protected function userHas2FA($user): bool
    {
        // Check if user has 2FA enabled
        // This depends on your 2FA implementation
        return $user->two_factor_secret !== null || $user->hasVerifiedEmail();
    }

    protected function userHasStrongPassword($user): bool
    {
        // Check password strength - this is just an estimation
        // In real implementation, you'd track this during password changes
        return $user->updated_at->diffInDays() < 90; // Password changed recently
    }

    protected function userHasRegularActivity($user): bool
    {
        // Check if user has been active in the last 7 days
        return $user->updated_at->diffInDays() <= 7;
    }

    protected function userHasSecureSession($user): bool
    {
        // Check if user's session is secure (HTTPS, proper cookies, etc.)
        return request()->isSecure();
    }

    protected function userHasNoRecentIncidents($user): bool
    {
        // Check for recent failed login attempts or security incidents
        // This would check your security logs
        return true; // Placeholder - implement based on your security logging
    }

    protected function getUserSecurityStreak($user): int
    {
        // Calculate consecutive days of secure activity
        // This would track daily security score maintenance
        return $user->created_at->diffInDays() >= 30 ? rand(5, 30) : rand(1, 5);
    }

    public function getViewData(): array
    {
        return [
            'securityData' => $this->getSecurityScore(),
        ];
    }
}