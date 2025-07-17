<?php

namespace Tests\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait AssertionHelper
{
    /**
     * Assert array has keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array missing key: {$key}");
        }
    }

    /**
     * Assert array structure matches
     */
    protected function assertArrayStructure(array $structure, array $array): void
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                $this->assertIsArray($array);
                foreach ($array as $arrayItem) {
                    $this->assertArrayStructure($structure['*'], $arrayItem);
                }
            } elseif (is_array($value)) {
                $this->assertArrayHasKey($key, $array);
                $this->assertArrayStructure($structure[$key], $array[$key]);
            } else {
                $this->assertArrayHasKey($value, $array);
            }
        }
    }

    /**
     * Assert string contains all substrings
     */
    protected function assertStringContainsAll(array $needles, string $haystack): void
    {
        foreach ($needles as $needle) {
            $this->assertStringContainsString(
                $needle,
                $haystack,
                "String does not contain: {$needle}"
            );
        }
    }

    /**
     * Assert string contains any substring
     */
    protected function assertStringContainsAny(array $needles, string $haystack): void
    {
        $found = false;
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue(
            $found,
            "String does not contain any of: " . implode(', ', $needles)
        );
    }

    /**
     * Assert value is UUID
     */
    protected function assertIsUuid($value): void
    {
        $this->assertTrue(
            Str::isUuid($value),
            "Failed asserting that '{$value}' is a valid UUID"
        );
    }

    /**
     * Assert value is email
     */
    protected function assertIsEmail($value): void
    {
        $this->assertTrue(
            filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            "Failed asserting that '{$value}' is a valid email"
        );
    }

    /**
     * Assert value is URL
     */
    protected function assertIsUrl($value): void
    {
        $this->assertTrue(
            filter_var($value, FILTER_VALIDATE_URL) !== false,
            "Failed asserting that '{$value}' is a valid URL"
        );
    }

    /**
     * Assert datetime is recent
     */
    protected function assertDateTimeIsRecent($datetime, int $seconds = 60): void
    {
        $date = Carbon::parse($datetime);
        $now = Carbon::now();
        
        $this->assertTrue(
            $date->diffInSeconds($now) <= $seconds,
            "DateTime {$datetime} is not within {$seconds} seconds of now"
        );
    }

    /**
     * Assert datetime is in format
     */
    protected function assertDateTimeFormat($datetime, string $format = 'Y-m-d H:i:s'): void
    {
        $parsed = \DateTime::createFromFormat($format, $datetime);
        
        $this->assertTrue(
            $parsed && $parsed->format($format) === $datetime,
            "DateTime '{$datetime}' does not match format '{$format}'"
        );
    }

    /**
     * Assert collection contains item
     */
    protected function assertCollectionContains($collection, callable $callback): void
    {
        $found = collect($collection)->first($callback) !== null;
        
        $this->assertTrue(
            $found,
            "Collection does not contain item matching callback"
        );
    }

    /**
     * Assert values are close
     */
    protected function assertClose(float $expected, float $actual, float $delta = 0.01): void
    {
        $this->assertTrue(
            abs($expected - $actual) <= $delta,
            "Failed asserting that {$actual} is close to {$expected} (delta: {$delta})"
        );
    }

    /**
     * Assert JSON structure and values
     */
    protected function assertJsonEquals(array $expected, $actual): void
    {
        if (is_string($actual)) {
            $actual = json_decode($actual, true);
        }
        
        $this->assertEquals(
            $expected,
            $actual,
            "JSON structure does not match expected"
        );
    }

    /**
     * Assert value is between
     */
    protected function assertBetween($value, $min, $max): void
    {
        $this->assertTrue(
            $value >= $min && $value <= $max,
            "Value {$value} is not between {$min} and {$max}"
        );
    }

    /**
     * Assert array is subset
     */
    protected function assertArraySubset(array $subset, array $array): void
    {
        $this->assertTrue(
            !array_diff_assoc($subset, $array),
            "Array subset assertion failed"
        );
    }

    /**
     * Assert exception contains message
     */
    protected function assertExceptionMessage(string $expected, \Exception $exception): void
    {
        $this->assertStringContainsString(
            $expected,
            $exception->getMessage(),
            "Exception message does not contain: {$expected}"
        );
    }

    /**
     * Assert value matches regex
     */
    protected function assertMatchesRegex(string $pattern, $value): void
    {
        $this->assertMatchesRegularExpression(
            $pattern,
            $value,
            "Value does not match regex: {$pattern}"
        );
    }

    /**
     * Assert file content contains
     */
    protected function assertFileContains(string $path, string $content): void
    {
        $this->assertFileExists($path);
        $fileContent = file_get_contents($path);
        
        $this->assertStringContainsString(
            $content,
            $fileContent,
            "File {$path} does not contain: {$content}"
        );
    }

    /**
     * Assert arrays are equal ignoring order
     */
    protected function assertArraysEqualIgnoringOrder(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);
        
        $this->assertEquals(
            $expected,
            $actual,
            "Arrays are not equal when ignoring order"
        );
    }

    /**
     * Assert value is one of
     */
    protected function assertIsOneOf($value, array $allowed): void
    {
        $this->assertContains(
            $value,
            $allowed,
            "Value '{$value}' is not one of: " . implode(', ', $allowed)
        );
    }

    /**
     * Assert value has type
     */
    protected function assertIsType($value, string $type): void
    {
        $method = 'assertIs' . ucfirst($type);
        
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->assertTrue(
                gettype($value) === $type,
                "Value is not of type {$type}"
            );
        }
    }

    /**
     * Assert value is positive
     */
    protected function assertPositive($value): void
    {
        $this->assertTrue(
            $value > 0,
            "Value {$value} is not positive"
        );
    }

    /**
     * Assert value is negative
     */
    protected function assertNegative($value): void
    {
        $this->assertTrue(
            $value < 0,
            "Value {$value} is not negative"
        );
    }

    /**
     * Assert execution time
     */
    protected function assertExecutionTime(callable $callback, float $maxSeconds): void
    {
        $start = microtime(true);
        $callback();
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(
            $maxSeconds,
            $duration,
            "Execution took {$duration}s, expected less than {$maxSeconds}s"
        );
    }

    /**
     * Assert no orphaned data
     */
    protected function assertNoOrphanedData(string $table, string $foreignKey, string $relatedTable): void
    {
        $orphaned = \DB::table($table)
            ->leftJoin($relatedTable, "{$table}.{$foreignKey}", '=', "{$relatedTable}.id")
            ->whereNull("{$relatedTable}.id")
            ->where("{$table}.{$foreignKey}", '!=', null)
            ->count();
            
        $this->assertEquals(
            0,
            $orphaned,
            "Found {$orphaned} orphaned records in {$table} table"
        );
    }
}