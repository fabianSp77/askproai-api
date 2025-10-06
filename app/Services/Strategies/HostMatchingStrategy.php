<?php

namespace App\Services\Strategies;

interface HostMatchingStrategy
{
    /**
     * Attempt to match Cal.com host to internal staff
     *
     * @param array $hostData Cal.com host object from API response
     * @param HostMatchContext $context Tenant and booking context
     * @return MatchResult|null Match result or null if no match found
     */
    public function match(array $hostData, HostMatchContext $context): ?MatchResult;

    /**
     * Get mapping source identifier for audit trail
     *
     * @return string One of: auto_email, auto_name, manual, admin
     */
    public function getSource(): string;

    /**
     * Get strategy priority (higher value = run first)
     *
     * @return int Priority value (0-100)
     */
    public function getPriority(): int;
}
