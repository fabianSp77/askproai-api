<?php

namespace App\ValueObjects;

class PolicyResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reason,
        public readonly float $fee = 0.0,
        public readonly array $details = []
    ) {
    }

    public static function allow(float $fee = 0.0, array $details = []): self
    {
        return new self(
            allowed: true,
            reason: 'Modification allowed',
            fee: $fee,
            details: $details
        );
    }

    public static function deny(string $reason, array $details = []): self
    {
        return new self(
            allowed: false,
            reason: $reason,
            fee: 0.0,
            details: $details
        );
    }

    public function withFee(float $fee): self
    {
        return new self(
            allowed: $this->allowed,
            reason: $this->reason,
            fee: $fee,
            details: $this->details
        );
    }

    public function withDetails(array $details): self
    {
        return new self(
            allowed: $this->allowed,
            reason: $this->reason,
            fee: $this->fee,
            details: array_merge($this->details, $details)
        );
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'reason' => $this->reason,
            'fee' => $this->fee,
            'details' => $this->details,
        ];
    }
}
