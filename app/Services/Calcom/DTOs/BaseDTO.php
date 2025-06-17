<?php

namespace App\Services\Calcom\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Base Data Transfer Object
 */
abstract class BaseDTO implements Arrayable, Jsonable
{
    /**
     * Create instance from array
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Convert to array
     */
    abstract public function toArray(): array;

    /**
     * Convert to JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Create collection from array of data
     */
    public static function collection(array $items): array
    {
        return array_map(fn($item) => static::fromArray($item), $items);
    }

    /**
     * Get value from array with default
     */
    protected static function getValue(array $data, string $key, $default = null)
    {
        return $data[$key] ?? $default;
    }

    /**
     * Parse datetime string to Carbon instance
     */
    protected static function parseDateTime(?string $datetime): ?\Carbon\Carbon
    {
        return $datetime ? \Carbon\Carbon::parse($datetime) : null;
    }
}