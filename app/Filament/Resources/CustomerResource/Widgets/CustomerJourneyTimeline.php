<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

/**
 * Customer Journey Timeline Widget
 *
 * Visual journey progression with next steps
 */
class CustomerJourneyTimeline extends Widget
{
    protected static string $view = 'filament.widgets.customer-journey-timeline';

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

        $journeyStages = [
            'initial_contact' => [
                'label' => 'Erstkontakt',
                'icon' => '📱',
                'description' => 'Erster Kontakt hergestellt',
                'order' => 1,
            ],
            'lead' => [
                'label' => 'Lead',
                'icon' => '🌱',
                'description' => 'Interesse vorhanden',
                'order' => 2,
            ],
            'prospect' => [
                'label' => 'Interessent',
                'icon' => '🔍',
                'description' => 'Aktiv im Gespräch',
                'order' => 3,
            ],
            'customer' => [
                'label' => 'Kunde',
                'icon' => '⭐',
                'description' => 'Erstkunde',
                'order' => 4,
            ],
            'regular' => [
                'label' => 'Stammkunde',
                'icon' => '💎',
                'description' => 'Wiederkehrender Kunde',
                'order' => 5,
            ],
            'vip' => [
                'label' => 'VIP',
                'icon' => '👑',
                'description' => 'Premium-Kunde',
                'order' => 6,
            ],
        ];

        $negativeStages = [
            'at_risk' => [
                'label' => 'Gefährdet',
                'icon' => '⚠️',
                'description' => 'Risiko der Abwanderung',
            ],
            'churned' => [
                'label' => 'Verloren',
                'icon' => '❌',
                'description' => 'Kunde abgewandert',
            ],
        ];

        $currentStage = $customer->journey_status;
        $currentStageInfo = $journeyStages[$currentStage] ?? $negativeStages[$currentStage] ?? null;

        // Determine if customer is on negative path
        $isNegativePath = isset($negativeStages[$currentStage]);

        // Calculate next steps
        $nextSteps = $this->getNextSteps($customer);

        // Get journey history
        $journeyHistory = $this->getJourneyHistory($customer);

        return [
            'journeyStages' => $journeyStages,
            'negativeStages' => $negativeStages,
            'currentStage' => $currentStage,
            'currentStageInfo' => $currentStageInfo,
            'isNegativePath' => $isNegativePath,
            'nextSteps' => $nextSteps,
            'journeyHistory' => $journeyHistory,
        ];
    }

    private function getNextSteps(Customer $customer): array
    {
        $steps = [];
        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();
        $hasEmail = !empty($customer->email);

        switch ($customer->journey_status) {
            case 'initial_contact':
                if ($callCount === 0) {
                    $steps[] = ['icon' => '📞', 'text' => 'Ersten Kontakt herstellen', 'priority' => 'high'];
                } else if ($appointmentCount === 0) {
                    $steps[] = ['icon' => '📅', 'text' => 'Ersten Termin buchen', 'priority' => 'high'];
                }
                if (!$hasEmail) {
                    $steps[] = ['icon' => '✉️', 'text' => 'E-Mail-Adresse erfassen', 'priority' => 'medium'];
                }
                break;

            case 'lead':
                $steps[] = ['icon' => '📞', 'text' => 'Follow-up Anruf durchführen', 'priority' => 'high'];
                $steps[] = ['icon' => '🎯', 'text' => 'Bedarf qualifizieren', 'priority' => 'medium'];
                break;

            case 'prospect':
                if ($appointmentCount === 0) {
                    $steps[] = ['icon' => '📅', 'text' => 'Beratungstermin vereinbaren', 'priority' => 'high'];
                } else {
                    $steps[] = ['icon' => '✨', 'text' => 'Zum Kunden konvertieren', 'priority' => 'high'];
                }
                break;

            case 'customer':
                $steps[] = ['icon' => '🔄', 'text' => 'Wiederkehrenden Termin anbieten', 'priority' => 'high'];
                $steps[] = ['icon' => '⭐', 'text' => 'Zufriedenheit abfragen', 'priority' => 'medium'];
                break;

            case 'regular':
                $steps[] = ['icon' => '📈', 'text' => 'Upselling-Möglichkeiten prüfen', 'priority' => 'medium'];
                $steps[] = ['icon' => '💎', 'text' => 'VIP-Status evaluieren', 'priority' => 'low'];
                break;

            case 'vip':
                $steps[] = ['icon' => '👑', 'text' => 'Premium-Service aufrechterhalten', 'priority' => 'high'];
                $steps[] = ['icon' => '🎁', 'text' => 'Exklusive Angebote senden', 'priority' => 'medium'];
                break;

            case 'at_risk':
                $steps[] = ['icon' => '🚨', 'text' => 'DRINGEND: Persönlich kontaktieren', 'priority' => 'critical'];
                $steps[] = ['icon' => '🎁', 'text' => 'Sonderangebot unterbreiten', 'priority' => 'high'];
                $steps[] = ['icon' => '📝', 'text' => 'Feedback einholen', 'priority' => 'high'];
                break;

            case 'churned':
                $steps[] = ['icon' => '🔄', 'text' => 'Win-back Kampagne starten', 'priority' => 'high'];
                $steps[] = ['icon' => '📧', 'text' => 'Reaktivierungs-Email senden', 'priority' => 'medium'];
                break;
        }

        return $steps;
    }

    private function getJourneyHistory(Customer $customer): array
    {
        // Try to parse journey_history JSON field if it exists
        if (!empty($customer->journey_history)) {
            try {
                $history = json_decode($customer->journey_history, true);
                if (is_array($history)) {
                    return array_slice($history, -5); // Last 5 changes
                }
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

        // Fallback: Create basic history from current state
        $changedAt = $customer->journey_status_updated_at ?? $customer->created_at;
        return [[
            'from' => null,
            'to' => $customer->journey_status,
            'changed_at' => $changedAt ? $changedAt->toIso8601String() : null,
            'note' => 'Aktueller Status',
        ]];
    }
}
