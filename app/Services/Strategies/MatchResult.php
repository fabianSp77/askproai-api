<?php

namespace App\Services\Strategies;

use App\Models\Staff;

/**
 * Data Transfer Object for host matching results
 */
class MatchResult
{
    public function __construct(
        public Staff $staff,
        public int $confidence,  // 0-100 confidence score
        public string $reason,   // Human-readable match reason
        public array $metadata = []  // Additional context
    ) {
        if ($this->confidence < 0 || $this->confidence > 100) {
            throw new \InvalidArgumentException("Confidence must be between 0 and 100, got {$this->confidence}");
        }
    }
}
