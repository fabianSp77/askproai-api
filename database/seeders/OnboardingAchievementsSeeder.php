<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnboardingAchievementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            // Onboarding milestones
            [
                'key' => 'first_steps',
                'name' => 'Erste Schritte',
                'description' => 'Unternehmensprofil erfolgreich eingerichtet',
                'icon' => 'heroicon-o-rocket-launch',
                'type' => 'milestone',
                'points' => 10,
                'requirements' => json_encode(['onboarding_step' => 'welcome']),
            ],
            [
                'key' => 'team_builder',
                'name' => 'Team-Builder',
                'description' => 'Ersten Mitarbeiter hinzugefügt',
                'icon' => 'heroicon-o-users',
                'type' => 'badge',
                'points' => 15,
                'requirements' => json_encode(['min_staff' => 1]),
            ],
            [
                'key' => 'service_creator',
                'name' => 'Service-Creator',
                'description' => 'Erste Dienstleistung erstellt',
                'icon' => 'heroicon-o-briefcase',
                'type' => 'badge',
                'points' => 15,
                'requirements' => json_encode(['min_services' => 1]),
            ],
            [
                'key' => 'integration_master',
                'name' => 'Integrations-Meister',
                'description' => 'Cal.com und Retell.ai erfolgreich verbunden',
                'icon' => 'heroicon-o-link',
                'type' => 'badge',
                'points' => 25,
                'requirements' => json_encode(['integrations' => ['calcom', 'retell']]),
            ],
            [
                'key' => 'onboarding_complete',
                'name' => 'Einrichtung abgeschlossen',
                'description' => 'Alle Onboarding-Schritte erfolgreich durchlaufen',
                'icon' => 'heroicon-o-check-badge',
                'type' => 'milestone',
                'points' => 50,
                'requirements' => json_encode(['onboarding_complete' => true]),
            ],
            [
                'key' => 'fast_learner',
                'name' => 'Schnelllerner',
                'description' => 'Onboarding in unter 30 Minuten abgeschlossen',
                'icon' => 'heroicon-o-clock',
                'type' => 'badge',
                'points' => 25,
                'requirements' => json_encode(['onboarding_time_minutes' => 30]),
            ],
            
            // Usage achievements
            [
                'key' => 'first_appointment',
                'name' => 'Erster Termin',
                'description' => 'Ersten Termin über das System gebucht',
                'icon' => 'heroicon-o-calendar',
                'type' => 'milestone',
                'points' => 20,
                'requirements' => json_encode(['min_appointments' => 1]),
            ],
            [
                'key' => 'appointment_10',
                'name' => 'Termin-Starter',
                'description' => '10 Termine erfolgreich verwaltet',
                'icon' => 'heroicon-o-calendar-days',
                'type' => 'badge',
                'points' => 30,
                'requirements' => json_encode(['min_appointments' => 10]),
            ],
            [
                'key' => 'appointment_100',
                'name' => 'Termin-Profi',
                'description' => '100 Termine erfolgreich verwaltet',
                'icon' => 'heroicon-o-trophy',
                'type' => 'badge',
                'points' => 100,
                'requirements' => json_encode(['min_appointments' => 100]),
            ],
            [
                'key' => 'multi_branch',
                'name' => 'Multi-Standort',
                'description' => 'Mehrere Standorte erfolgreich eingerichtet',
                'icon' => 'heroicon-o-map',
                'type' => 'badge',
                'points' => 30,
                'requirements' => json_encode(['min_branches' => 2]),
            ],
            [
                'key' => 'team_10',
                'name' => 'Großes Team',
                'description' => '10 oder mehr Mitarbeiter im System',
                'icon' => 'heroicon-o-user-group',
                'type' => 'badge',
                'points' => 40,
                'requirements' => json_encode(['min_staff' => 10]),
            ],
            
            // Engagement achievements
            [
                'key' => 'daily_user',
                'name' => 'Täglicher Nutzer',
                'description' => '7 Tage in Folge angemeldet',
                'icon' => 'heroicon-o-fire',
                'type' => 'badge',
                'points' => 20,
                'requirements' => json_encode(['consecutive_days' => 7]),
            ],
            [
                'key' => 'tutorial_master',
                'name' => 'Tutorial-Meister',
                'description' => 'Alle verfügbaren Tutorials abgeschlossen',
                'icon' => 'heroicon-o-academic-cap',
                'type' => 'badge',
                'points' => 30,
                'requirements' => json_encode(['all_tutorials' => true]),
            ],
            [
                'key' => 'feedback_giver',
                'name' => 'Feedback-Geber',
                'description' => 'Erstes Feedback oder Bewertung abgegeben',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'type' => 'badge',
                'points' => 15,
                'requirements' => json_encode(['has_feedback' => true]),
            ],
            
            // Special achievements
            [
                'key' => 'early_adopter',
                'name' => 'Early Adopter',
                'description' => 'Zu den ersten 100 Nutzern von AskProAI',
                'icon' => 'heroicon-o-star',
                'type' => 'reward',
                'points' => 100,
                'requirements' => json_encode(['user_id_max' => 100]),
            ],
            [
                'key' => 'perfect_month',
                'name' => 'Perfekter Monat',
                'description' => 'Keine verpassten Termine in einem Monat',
                'icon' => 'heroicon-o-shield-check',
                'type' => 'badge',
                'points' => 50,
                'requirements' => json_encode(['no_shows_month' => 0]),
            ],
        ];

        foreach ($achievements as $achievement) {
            DB::table('onboarding_achievements')->insertOrIgnore([
                'key' => $achievement['key'],
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'icon' => $achievement['icon'],
                'type' => $achievement['type'],
                'points' => $achievement['points'],
                'requirements' => $achievement['requirements'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}