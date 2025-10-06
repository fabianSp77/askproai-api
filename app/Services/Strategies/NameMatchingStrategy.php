<?php

namespace App\Services\Strategies;

use App\Models\Staff;
use Illuminate\Support\Str;

/**
 * Name-based host matching strategy
 * Matches Cal.com host name to staff first_name + last_name with 75% confidence
 * Priority: 50 (medium - name matching is less reliable than email)
 */
class NameMatchingStrategy implements HostMatchingStrategy
{
    public function match(array $hostData, HostMatchContext $context): ?MatchResult
    {
        $calcomName = $hostData['name'] ?? null;

        if (!$calcomName) {
            return null;
        }

        // Normalize: "Fabian Spitzer" -> "fabian spitzer"
        $normalizedCalcomName = Str::lower(trim($calcomName));

        $staff = Staff::query()
            ->where('company_id', $context->companyId)
            ->where('is_active', true)
            ->get()
            ->first(function ($staff) use ($normalizedCalcomName) {
                $staffName = Str::lower(trim($staff->name ?? ''));
                return $staffName === $normalizedCalcomName;
            });

        if (!$staff) {
            return null;
        }

        return new MatchResult(
            staff: $staff,
            confidence: 75, // Lower confidence than email
            reason: "Full name match: {$calcomName}",
            metadata: [
                'match_field' => 'name',
                'match_value' => $calcomName,
                'staff_name' => $staff->name,
                'strategy' => 'NameMatchingStrategy'
            ]
        );
    }

    public function getSource(): string
    {
        return 'auto_name';
    }

    public function getPriority(): int
    {
        return 50; // Lower priority than email
    }
}
