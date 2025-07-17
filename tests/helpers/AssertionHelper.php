<?php

namespace Tests\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Custom assertions for testing
 */
trait AssertionHelper
{
    /**
     * Assert that a date is within a range
     */
    protected function assertDateWithinRange($date, Carbon $start, Carbon $end, string $message = ''): void
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $this->assertTrue(
            $date->between($start, $end),
            $message ?: "Date {$date} is not between {$start} and {$end}"
        );
    }

    /**
     * Assert that a collection contains items matching criteria
     */
    protected function assertCollectionContains(Collection $collection, callable $callback, string $message = ''): void
    {
        $found = $collection->first($callback);
        
        $this->assertNotNull(
            $found,
            $message ?: 'Collection does not contain an item matching the criteria'
        );
    }

    /**
     * Assert that all items in collection match criteria
     */
    protected function assertCollectionAll(Collection $collection, callable $callback, string $message = ''): void
    {
        $allMatch = $collection->every($callback);
        
        $this->assertTrue(
            $allMatch,
            $message ?: 'Not all items in collection match the criteria'
        );
    }

    /**
     * Assert array has keys with specific types
     */
    protected function assertArrayHasKeysWithTypes(array $array, array $keysAndTypes): void
    {
        foreach ($keysAndTypes as $key => $type) {
            $this->assertArrayHasKey($key, $array, "Array missing key: {$key}");
            
            $value = $array[$key];
            $actualType = gettype($value);
            
            if ($type === 'array' && is_object($value)) {
                // Allow stdClass objects when expecting array
                continue;
            }
            
            $this->assertEquals(
                $type,
                $actualType,
                "Key '{$key}' expected to be {$type}, but is {$actualType}"
            );
        }
    }

    /**
     * Assert string matches pattern with wildcards
     */
    protected function assertStringMatchesPattern(string $pattern, string $string, string $message = ''): void
    {
        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';
        
        $this->assertMatchesRegularExpression(
            $regex,
            $string,
            $message ?: "String '{$string}' does not match pattern '{$pattern}'"
        );
    }

    /**
     * Assert that execution time is within limits
     */
    protected function assertExecutionTime(callable $callback, int $maxMilliseconds, string $message = ''): void
    {
        $start = microtime(true);
        $callback();
        $executionTime = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(
            $maxMilliseconds,
            $executionTime,
            $message ?: "Execution time ({$executionTime}ms) exceeded limit ({$maxMilliseconds}ms)"
        );
    }

    /**
     * Assert that a value is a valid UUID
     */
    protected function assertIsUuid($value, string $message = ''): void
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        
        $this->assertMatchesRegularExpression(
            $pattern,
            $value,
            $message ?: "Value '{$value}' is not a valid UUID"
        );
    }

    /**
     * Assert that a value is a valid email
     */
    protected function assertIsEmail($value, string $message = ''): void
    {
        $this->assertTrue(
            filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            $message ?: "Value '{$value}' is not a valid email address"
        );
    }

    /**
     * Assert that a value is a valid phone number
     */
    protected function assertIsPhoneNumber($value, string $message = ''): void
    {
        $pattern = '/^\+?[1-9]\d{1,14}$/'; // E.164 format
        
        $this->assertMatchesRegularExpression(
            $pattern,
            $value,
            $message ?: "Value '{$value}' is not a valid phone number"
        );
    }

    /**
     * Assert that arrays are equal ignoring order
     */
    protected function assertArraysEqualIgnoringOrder(array $expected, array $actual, string $message = ''): void
    {
        sort($expected);
        sort($actual);
        
        $this->assertEquals(
            $expected,
            $actual,
            $message ?: 'Arrays are not equal when ignoring order'
        );
    }

    /**
     * Assert that a JSON string contains specific structure
     */
    protected function assertJsonStringContainsStructure(string $json, array $structure): void
    {
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded, 'Invalid JSON string provided');
        $this->assertArrayHasKeysRecursive($decoded, $structure);
    }

    /**
     * Assert array has keys recursively
     */
    protected function assertArrayHasKeysRecursive(array $array, array $keys): void
    {
        foreach ($keys as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $array);
                $this->assertIsArray($array[$key]);
                $this->assertArrayHasKeysRecursive($array[$key], $value);
            } else {
                $this->assertArrayHasKey($value, $array);
            }
        }
    }

    /**
     * Assert that a callback throws specific exception
     */
    protected function assertThrowsException(callable $callback, string $exceptionClass, ?string $expectedMessage = null): void
    {
        try {
            $callback();
            $this->fail("Expected exception {$exceptionClass} was not thrown");
        } catch (\Exception $e) {
            $this->assertInstanceOf($exceptionClass, $e);
            
            if ($expectedMessage !== null) {
                $this->assertEquals($expectedMessage, $e->getMessage());
            }
        }
    }

    /**
     * Assert that two floats are equal within tolerance
     */
    protected function assertFloatsEqual(float $expected, float $actual, float $tolerance = 0.0001, string $message = ''): void
    {
        $this->assertTrue(
            abs($expected - $actual) < $tolerance,
            $message ?: "Floats not equal: expected {$expected}, got {$actual} (tolerance: {$tolerance})"
        );
    }

    /**
     * Assert that a string is valid JSON
     */
    protected function assertIsValidJson($string, string $message = ''): void
    {
        json_decode($string);
        
        $this->assertEquals(
            JSON_ERROR_NONE,
            json_last_error(),
            $message ?: 'String is not valid JSON: ' . json_last_error_msg()
        );
    }

    /**
     * Assert memory usage is within limits
     */
    protected function assertMemoryUsage(callable $callback, int $maxMegabytes, string $message = ''): void
    {
        $startMemory = memory_get_usage();
        $callback();
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;
        
        $this->assertLessThan(
            $maxMegabytes,
            $memoryUsed,
            $message ?: "Memory usage ({$memoryUsed}MB) exceeded limit ({$maxMegabytes}MB)"
        );
    }
}