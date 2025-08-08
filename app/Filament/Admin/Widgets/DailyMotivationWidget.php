<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class DailyMotivationWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.daily-motivation';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1; // Show at top
    
    public function getViewData(): array 
    {
        $hour = now()->format('H');
        $userName = auth()->user()->name ?? 'Champion';
        
        // Get daily stats
        $todayStats = $this->getDailyStats();
        
        // Get motivational content based on time and performance
        $motivation = $this->getContextualMotivation($hour, $todayStats);
        
        // Get achievements
        $achievements = $this->checkTodayAchievements($todayStats);
        
        return [
            'motivation' => $motivation,
            'achievements' => $achievements,
            'stats' => $todayStats,
            'userName' => $userName,
            'timeOfDay' => $this->getTimeOfDayMessage($hour),
            'progressRing' => $this->calculateProgressRing($todayStats),
            'teamStats' => $this->getTeamStats(),
        ];
    }
    
    private function getDailyStats(): array
    {
        return Cache::remember('daily_stats_' . auth()->id(), now()->addHour(), function() {
            $today = today();
            
            return [
                'calls' => \App\Models\Call::whereDate('created_at', $today)->count(),
                'appointments' => \App\Models\Call::whereDate('created_at', $today)->where('appointment_made', true)->count(),
                'total_duration' => \App\Models\Call::whereDate('created_at', $today)->sum('duration_sec'),
                'avg_duration' => \App\Models\Call::whereDate('created_at', $today)->avg('duration_sec'),
                'success_rate' => $this->calculateSuccessRate($today),
            ];
        });
    }
    
    private function calculateSuccessRate($today): float
    {
        $totalCalls = \App\Models\Call::whereDate('created_at', $today)->count();
        if ($totalCalls === 0) return 0;
        
        $successfulCalls = \App\Models\Call::whereDate('created_at', $today)
            ->where('appointment_made', true)
            ->count();
            
        return round(($successfulCalls / $totalCalls) * 100, 1);
    }
    
    private function getContextualMotivation($hour, $stats): array
    {
        $messages = [];
        
        // Time-based motivation
        if ($hour < 9) {
            $messages['timeGreeting'] = '🌅 Früher Start! Bereit für einen erfolgreichen Tag?';
        } elseif ($hour < 12) {
            $messages['timeGreeting'] = '☀️ Vormittags-Power! Die beste Zeit für wichtige Gespräche!';
        } elseif ($hour < 15) {
            $messages['timeGreeting'] = '🚀 Mittagsenergie! Jetzt geht es richtig los!';
        } elseif ($hour < 18) {
            $messages['timeGreeting'] = '💪 Nachmittags-Champion! Endspurt mit Stil!';
        } else {
            $messages['timeGreeting'] = '🌆 Feierabend naht - du warst heute spitze!';
        }
        
        // Performance-based motivation
        if ($stats['calls'] >= 20) {
            $messages['performance'] = '🔥 Unglaublich! ' . $stats['calls'] . ' Anrufe heute - Du bist on fire!';
        } elseif ($stats['calls'] >= 10) {
            $messages['performance'] = '⭐ Fantastisch! ' . $stats['calls'] . ' Anrufe - Das Call Center brummt!';
        } elseif ($stats['calls'] >= 5) {
            $messages['performance'] = '💼 Stark! ' . $stats['calls'] . ' Anrufe - Professional wie immer!';
        } elseif ($stats['calls'] > 0) {
            $messages['performance'] = '🎯 Gut gestartet! ' . $stats['calls'] . ' Anrufe - Jeder zählt!';
        } else {
            $messages['performance'] = '✨ Ruhiger Start - gleich wird es bestimmt lebendiger!';
        }
        
        // Success rate motivation
        if ($stats['success_rate'] >= 30) {
            $messages['success'] = '🏆 Termin-Champion! ' . $stats['success_rate'] . '% Erfolgsquote - Weltklasse!';
        } elseif ($stats['success_rate'] >= 20) {
            $messages['success'] = '🎉 Super Quote! ' . $stats['success_rate'] . '% - Du weißt wie es geht!';
        } elseif ($stats['success_rate'] >= 10) {
            $messages['success'] = '📈 Gute Arbeit! ' . $stats['success_rate'] . '% - Trend nach oben!';
        }
        
        // Random motivational quote
        $quotes = [
            'Service-Excellence ist kein Zufall - es ist deine Haltung! 💫',
            'Jeder Anruf ist eine Chance, jemandes Tag zu verbessern! 🌟',
            'Du bist nicht nur ein Agent - du bist ein Erlebnis-Schaffer! 🎨',
            'Deutsche Gründlichkeit + Herzlichkeit = Unschlagbar! 🇩🇪',
            'Lächeln kann man hören - auch am Telefon! 😊',
            'Probleme sind nur Lösungen in Verkleidung! 🕵️',
            'Kunden vergessen nie, wie du sie behandelt hast! 💖'
        ];
        
        $messages['quote'] = $quotes[array_rand($quotes)];
        
        return $messages;
    }
    
    private function checkTodayAchievements($stats): array
    {
        $achievements = [];
        $today = now()->format('Y-m-d');
        $shownToday = json_decode(cache()->get('achievements_shown_' . auth()->id() . '_' . $today, '[]'), true);
        
        // Early bird (first call before 9 AM)
        if (now()->format('H') < 9 && $stats['calls'] >= 1 && !in_array('early_bird', $shownToday)) {
            $achievements[] = [
                'type' => 'early_bird',
                'title' => '🌅 Frühaufsteher!',
                'message' => 'Erster Anruf vor 9 Uhr gemeistert!',
                'color' => 'success'
            ];
        }
        
        // Speed demon (average call under 60 seconds but still productive)
        if ($stats['avg_duration'] > 0 && $stats['avg_duration'] < 60 && $stats['calls'] >= 5 && !in_array('speed_demon', $shownToday)) {
            $achievements[] = [
                'type' => 'speed_demon',
                'title' => '⚡ Blitzschnell!',
                'message' => 'Effizient und zielgerichtet - unter 1 Min Durchschnitt!',
                'color' => 'warning'
            ];
        }
        
        // Consultant (average call over 5 minutes)
        if ($stats['avg_duration'] > 300 && !in_array('consultant', $shownToday)) {
            $achievements[] = [
                'type' => 'consultant',
                'title' => '🧠 Beratungs-Profi!',
                'message' => 'Über 5 Min Durchschnitt - ausführliche Betreuung!',
                'color' => 'primary'
            ];
        }
        
        // Appointment wizard
        if ($stats['appointments'] >= 3 && !in_array('appointment_wizard', $shownToday)) {
            $achievements[] = [
                'type' => 'appointment_wizard',
                'title' => '🦅 Termin-Zauberer!',
                'message' => $stats['appointments'] . ' Termine heute gebucht - Magie!',
                'color' => 'success'
            ];
        }
        
        // Call marathon
        if ($stats['calls'] >= 25 && !in_array('call_marathon', $shownToday)) {
            $achievements[] = [
                'type' => 'call_marathon',
                'title' => '🏃 Anruf-Marathon!',
                'message' => $stats['calls'] . ' Gespräche - Ausdauer-Champion!',
                'color' => 'danger'
            ];
        }
        
        // Cache shown achievements
        if (!empty($achievements)) {
            $newShown = array_merge($shownToday, array_column($achievements, 'type'));
            cache()->put('achievements_shown_' . auth()->id() . '_' . $today, json_encode($newShown), now()->addDay());
        }
        
        return $achievements;
    }
    
    private function getTimeOfDayMessage($hour): string
    {
        if ($hour < 6) return 'Nachtschicht-Held! 🌙';
        if ($hour < 9) return 'Früher Vogel! 🐦';  
        if ($hour < 12) return 'Vormittags-Power! ☀️';
        if ($hour < 15) return 'Mittagsenergie! 🚀';
        if ($hour < 18) return 'Nachmittags-Champion! 💪';
        if ($hour < 22) return 'Abend-Profi! 🌆';
        return 'Spätschicht-Superheld! ⭐';
    }
    
    private function calculateProgressRing($stats): array
    {
        $dailyGoals = [
            'calls' => 15,
            'appointments' => 3,
            'duration' => 1800, // 30 minutes total
        ];
        
        return [
            'calls' => min(100, ($stats['calls'] / $dailyGoals['calls']) * 100),
            'appointments' => min(100, ($stats['appointments'] / $dailyGoals['appointments']) * 100),
            'duration' => min(100, ($stats['total_duration'] / $dailyGoals['duration']) * 100),
        ];
    }
    
    private function getTeamStats(): array
    {
        return Cache::remember('team_stats_today', now()->addMinutes(10), function() {
            $today = today();
            
            return [
                'total_calls' => \App\Models\Call::whereDate('created_at', $today)->count(),
                'total_appointments' => \App\Models\Call::whereDate('created_at', $today)
                    ->where('appointment_made', true)->count(),
                'top_performer' => $this->getTopPerformer($today),
                'team_mood' => $this->calculateTeamMood(),
            ];
        });
    }
    
    private function getTopPerformer($today): ?array
    {
        // This would require user tracking on calls
        // For now, return motivational team message
        return [
            'name' => 'Das ganze Team',
            'message' => 'Gemeinsam unschlagbar! 💪'
        ];
    }
    
    private function calculateTeamMood(): string 
    {
        $hour = now()->format('H');
        $moods = [
            '6-9' => ['🌅', 'Motiviert in den Tag!'],
            '9-12' => ['☀️', 'Produktive Vormittags-Energie!'], 
            '12-15' => ['🚀', 'Mittagspower im Call Center!'],
            '15-18' => ['💪', 'Starker Endspurt!'],
            '18-22' => ['🌆', 'Entspannte Abendrunde!'],
        ];
        
        foreach ($moods as $timeRange => $mood) {
            [$start, $end] = explode('-', $timeRange);
            if ($hour >= $start && $hour < $end) {
                return $mood[0] . ' ' . $mood[1];
            }
        }
        
        return '⭐ Nachtschicht-Heroes!';
    }
    
    public static function canView(): bool
    {
        return true; // Show to all call center agents
    }
}