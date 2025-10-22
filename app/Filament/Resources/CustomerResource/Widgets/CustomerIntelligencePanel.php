<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

/**
 * Customer Intelligence Panel
 *
 * AI-powered insights and recommendations:
 * - Health Score (engagement, activity, conversion)
 * - Churn Risk Assessment
 * - Value Score (revenue potential)
 * - Next Best Action recommendations
 * - Key insights and patterns
 */
class CustomerIntelligencePanel extends Widget
{
    protected static string $view = 'filament.widgets.customer-intelligence-panel';

    #[Reactive]
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        if (!$this->record) {
            return [];
        }

        /** @var Customer $customer */
        $customer = $this->record;

        return [
            'customer' => $customer,
            'healthScore' => $this->calculateHealthScore($customer),
            'churnRisk' => $this->assessChurnRisk($customer),
            'valueScore' => $this->calculateValueScore($customer),
            'engagementLevel' => $this->assessEngagementLevel($customer),
            'insights' => $this->generateInsights($customer),
            'nextBestAction' => $this->determineNextBestAction($customer),
        ];
    }

    /**
     * Calculate overall customer health (0-100)
     */
    private function calculateHealthScore(Customer $customer): array
    {
        $score = 50; // Base score

        // Positive factors
        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();
        $hasEmail = !empty($customer->email);
        $conversionRate = $callCount > 0 ? ($appointmentCount / $callCount) * 100 : 0;

        // Calls boost score
        $score += min($callCount * 5, 20); // Max +20

        // Appointments boost score significantly
        $score += min($appointmentCount * 10, 30); // Max +30

        // Email contact boosts score
        if ($hasEmail) {
            $score += 5;
        }

        // High conversion rate is excellent
        if ($conversionRate > 50) {
            $score += 15;
        } elseif ($conversionRate > 25) {
            $score += 8;
        }

        // Negative factors
        $daysSinceLastContact = $this->getDaysSinceLastContact($customer);
        if ($daysSinceLastContact > 90) {
            $score -= 30;
        } elseif ($daysSinceLastContact > 60) {
            $score -= 20;
        } elseif ($daysSinceLastContact > 30) {
            $score -= 10;
        }

        // Failed bookings are bad
        $failedBookings = $customer->calls()
            ->where('appointment_made', 1)
            ->whereNull('converted_appointment_id')
            ->count();
        $score -= $failedBookings * 10;

        // Clamp to 0-100
        $score = max(0, min(100, $score));

        return [
            'value' => $score,
            'label' => $this->getHealthLabel($score),
            'color' => $this->getHealthColor($score),
        ];
    }

    /**
     * Assess churn risk (low, medium, high, critical)
     */
    private function assessChurnRisk(Customer $customer): array
    {
        $riskScore = 0;

        $daysSinceLastContact = $this->getDaysSinceLastContact($customer);
        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();

        // Time-based risk
        if ($daysSinceLastContact > 180) {
            $riskScore += 40;
        } elseif ($daysSinceLastContact > 90) {
            $riskScore += 30;
        } elseif ($daysSinceLastContact > 60) {
            $riskScore += 15;
        }

        // Activity-based risk
        if ($callCount === 0 && $appointmentCount === 0) {
            $riskScore += 30;
        } elseif ($appointmentCount === 0 && $callCount > 0) {
            $riskScore += 20; // Calls but no conversion
        }

        // Failed bookings indicate problems
        $failedBookings = $customer->calls()
            ->where('appointment_made', 1)
            ->whereNull('converted_appointment_id')
            ->count();
        $riskScore += $failedBookings * 15;

        // Cancelled appointments indicate dissatisfaction
        $cancelledAppointments = $customer->appointments()
            ->where('status', 'cancelled')
            ->count();
        $riskScore += $cancelledAppointments * 10;

        // Journey status considerations
        if ($customer->journey_status === 'churned') {
            $riskScore = 100; // Already lost
        } elseif ($customer->journey_status === 'at_risk') {
            $riskScore += 25;
        }

        $riskScore = min(100, $riskScore);

        return [
            'score' => $riskScore,
            'level' => match(true) {
                $riskScore >= 75 => 'critical',
                $riskScore >= 50 => 'high',
                $riskScore >= 25 => 'medium',
                default => 'low',
            },
            'label' => match(true) {
                $riskScore >= 75 => 'Kritisch',
                $riskScore >= 50 => 'Hoch',
                $riskScore >= 25 => 'Mittel',
                default => 'Niedrig',
            },
            'color' => match(true) {
                $riskScore >= 75 => 'danger',
                $riskScore >= 50 => 'warning',
                $riskScore >= 25 => 'info',
                default => 'success',
            },
        ];
    }

    /**
     * Calculate customer value score (0-100)
     */
    private function calculateValueScore(Customer $customer): array
    {
        $score = 20; // Base score

        $appointmentCount = $customer->appointments()->count();
        $totalRevenue = $customer->total_revenue ?? 0;
        $callCount = $customer->calls()->count();

        // Revenue is king
        if ($totalRevenue > 1000) {
            $score += 40;
        } elseif ($totalRevenue > 500) {
            $score += 30;
        } elseif ($totalRevenue > 100) {
            $score += 20;
        } elseif ($totalRevenue > 0) {
            $score += 10;
        }

        // Appointment frequency
        $score += min($appointmentCount * 8, 40);

        // Engagement potential
        if ($callCount > 5) {
            $score += 10;
        }

        // Email enables marketing
        if (!empty($customer->email)) {
            $score += 5;
        }

        $score = min(100, $score);

        return [
            'value' => $score,
            'label' => match(true) {
                $score >= 75 => 'Sehr hoch',
                $score >= 50 => 'Hoch',
                $score >= 25 => 'Mittel',
                default => 'Niedrig',
            },
            'color' => match(true) {
                $score >= 75 => 'success',
                $score >= 50 => 'primary',
                $score >= 25 => 'info',
                default => 'gray',
            },
        ];
    }

    /**
     * Assess engagement level
     */
    private function assessEngagementLevel(Customer $customer): array
    {
        $daysSinceLastContact = $this->getDaysSinceLastContact($customer);
        $callsLast30Days = $customer->calls()->where('created_at', '>=', now()->subDays(30))->count();
        $appointmentsLast30Days = $customer->appointments()->where('created_at', '>=', now()->subDays(30))->count();

        $engagementScore = 0;

        // Recent activity
        if ($daysSinceLastContact < 7) {
            $engagementScore += 40;
        } elseif ($daysSinceLastContact < 30) {
            $engagementScore += 20;
        }

        // Activity frequency
        $engagementScore += min($callsLast30Days * 10, 30);
        $engagementScore += min($appointmentsLast30Days * 15, 30);

        return [
            'score' => min(100, $engagementScore),
            'level' => match(true) {
                $engagementScore >= 70 => 'high',
                $engagementScore >= 40 => 'medium',
                default => 'low',
            },
            'label' => match(true) {
                $engagementScore >= 70 => 'Sehr aktiv',
                $engagementScore >= 40 => 'Mäßig aktiv',
                default => 'Wenig aktiv',
            },
            'color' => match(true) {
                $engagementScore >= 70 => 'success',
                $engagementScore >= 40 => 'info',
                default => 'warning',
            },
        ];
    }

    /**
     * Generate actionable insights
     */
    private function generateInsights(Customer $customer): array
    {
        $insights = [];

        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();
        $failedBookings = $customer->calls()
            ->where('appointment_made', 1)
            ->whereNull('converted_appointment_id')
            ->count();
        $daysSinceLastContact = $this->getDaysSinceLastContact($customer);

        // Failed bookings insight
        if ($failedBookings > 0) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Fehlgeschlagene Buchungen',
                'message' => "{$failedBookings} Termin" . ($failedBookings === 1 ? '' : 'e') . " konnte(n) nicht automatisch gebucht werden. Manuelle Nachbearbeitung erforderlich.",
                'action' => 'Jetzt nachbuchen',
            ];
        }

        // No appointments despite calls
        if ($callCount > 0 && $appointmentCount === 0) {
            $insights[] = [
                'type' => 'danger',
                'icon' => '🎯',
                'title' => 'Keine Conversion',
                'message' => "{$callCount} Anruf" . ($callCount === 1 ? '' : 'e') . ", aber noch kein Termin gebucht. Dringend nachfassen!",
                'action' => 'Termin buchen',
            ];
        }

        // Inactive customer
        if ($daysSinceLastContact > 60) {
            $insights[] = [
                'type' => 'info',
                'icon' => '💤',
                'title' => 'Inaktiver Kunde',
                'message' => "Letzter Kontakt vor {$daysSinceLastContact} Tagen. Reaktivierung empfohlen.",
                'action' => 'Jetzt kontaktieren',
            ];
        }

        // Missing email
        if (empty($customer->email)) {
            $insights[] = [
                'type' => 'info',
                'icon' => '✉️',
                'title' => 'Keine E-Mail-Adresse',
                'message' => 'E-Mail-Adresse fehlt. Marketing und Erinnerungen nicht möglich.',
                'action' => 'E-Mail erfassen',
            ];
        }

        // High value customer
        if (($customer->total_revenue ?? 0) > 500) {
            $insights[] = [
                'type' => 'success',
                'icon' => '💎',
                'title' => 'Wertvoller Kunde',
                'message' => 'Hoher Umsatz (€' . number_format($customer->total_revenue, 2) . '). VIP-Service empfohlen.',
                'action' => null,
            ];
        }

        return $insights;
    }

    /**
     * Determine next best action
     */
    private function determineNextBestAction(Customer $customer): array
    {
        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();
        $failedBookings = $customer->calls()
            ->where('appointment_made', 1)
            ->whereNull('converted_appointment_id')
            ->count();
        $daysSinceLastContact = $this->getDaysSinceLastContact($customer);

        // Priority 1: Failed bookings
        if ($failedBookings > 0) {
            return [
                'action' => 'Fehlgeschlagene Termine nachbuchen',
                'priority' => 'critical',
                'reason' => 'Agent konnte Termine nicht automatisch buchen',
                'icon' => '🚨',
            ];
        }

        // Priority 2: Calls but no appointments
        if ($callCount > 0 && $appointmentCount === 0) {
            return [
                'action' => 'Ersten Termin vereinbaren',
                'priority' => 'high',
                'reason' => 'Kunde hat angerufen aber noch keinen Termin',
                'icon' => '📅',
            ];
        }

        // Priority 3: Long inactivity
        if ($daysSinceLastContact > 90) {
            return [
                'action' => 'Reaktivierungskampagne starten',
                'priority' => 'high',
                'reason' => "Keine Aktivität seit {$daysSinceLastContact} Tagen",
                'icon' => '🔄',
            ];
        }

        // Priority 4: Missing email
        if (empty($customer->email)) {
            return [
                'action' => 'E-Mail-Adresse erfassen',
                'priority' => 'medium',
                'reason' => 'Für Marketing und Erinnerungen erforderlich',
                'icon' => '✉️',
            ];
        }

        // Priority 5: Regular follow-up
        if ($appointmentCount > 0) {
            return [
                'action' => 'Follow-up Termin anbieten',
                'priority' => 'low',
                'reason' => 'Stammkunde pflegen',
                'icon' => '⭐',
            ];
        }

        // Default
        return [
            'action' => 'Ersten Kontakt herstellen',
            'priority' => 'medium',
            'reason' => 'Noch keine Interaktion mit diesem Kunden',
            'icon' => '📞',
        ];
    }

    private function getDaysSinceLastContact(Customer $customer): int
    {
        $lastCall = $customer->calls()->latest('created_at')->first();
        $lastAppointment = $customer->appointments()->latest('created_at')->first();

        $lastContactAt = null;

        if ($lastCall && $lastAppointment) {
            $lastContactAt = $lastCall->created_at > $lastAppointment->created_at
                ? $lastCall->created_at
                : $lastAppointment->created_at;
        } elseif ($lastCall) {
            $lastContactAt = $lastCall->created_at;
        } elseif ($lastAppointment) {
            $lastContactAt = $lastAppointment->created_at;
        } else {
            $lastContactAt = $customer->created_at;
        }

        return (int) now()->diffInDays($lastContactAt);
    }

    private function getHealthLabel(int $score): string
    {
        return match(true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Gut',
            $score >= 40 => 'Befriedigend',
            $score >= 20 => 'Problematisch',
            default => 'Kritisch',
        };
    }

    private function getHealthColor(int $score): string
    {
        return match(true) {
            $score >= 80 => 'success',
            $score >= 60 => 'primary',
            $score >= 40 => 'info',
            $score >= 20 => 'warning',
            default => 'danger',
        };
    }
}
