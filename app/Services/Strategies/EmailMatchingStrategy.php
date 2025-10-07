<?php

namespace App\Services\Strategies;

use App\Models\Staff;

/**
 * Email-based host matching strategy
 * Matches Cal.com host email to staff email with 95% confidence
 * Priority: 100 (highest - email is most reliable identifier)
 */
class EmailMatchingStrategy implements HostMatchingStrategy
{
    public function match(array $hostData, HostMatchContext $context): ?MatchResult
    {
        $email = $hostData['email'] ?? null;

        if (!$email) {
            return null;
        }

        $staff = Staff::query()
            ->where('company_id', $context->companyId)
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$staff) {
            return null;
        }

        return new MatchResult(
            staff: $staff,
            confidence: config('booking.staff_matching.email_confidence', 95),
            reason: "Exact email match: {$email}",
            metadata: [
                'match_field' => 'email',
                'match_value' => $email,
                'strategy' => 'EmailMatchingStrategy'
            ]
        );
    }

    public function getSource(): string
    {
        return 'auto_email';
    }

    public function getPriority(): int
    {
        return 100; // Highest priority - email is most reliable
    }
}
