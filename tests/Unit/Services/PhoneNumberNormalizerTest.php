<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PhoneNumberNormalizer;

/**
 * PhoneNumberNormalizer Unit Tests
 *
 * Comprehensive test coverage for phone number normalization logic.
 * Tests handle German, international, and edge-case phone number formats.
 *
 * @covers \App\Services\PhoneNumberNormalizer
 */
class PhoneNumberNormalizerTest extends TestCase
{
    /**
     * Test normalization of standard German phone numbers
     *
     * @dataProvider germanPhoneNumberProvider
     */
    public function test_normalizes_german_phone_numbers(string $input, string $expected): void
    {
        $result = PhoneNumberNormalizer::normalize($input, 'DE');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test normalization of international phone numbers
     *
     * @dataProvider internationalPhoneNumberProvider
     */
    public function test_normalizes_international_phone_numbers(string $input, string $expected, string $country): void
    {
        $result = PhoneNumberNormalizer::normalize($input, $country);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that invalid phone numbers return null
     *
     * @dataProvider invalidPhoneNumberProvider
     */
    public function test_returns_null_for_invalid_numbers($input): void
    {
        $result = PhoneNumberNormalizer::normalize($input);
        $this->assertNull($result);
    }

    /**
     * Test E.164 format validation
     *
     * @dataProvider e164ValidationProvider
     */
    public function test_validates_e164_format(string $input, bool $expected): void
    {
        $result = PhoneNumberNormalizer::isE164Format($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test phone number comparison
     */
    public function test_compares_phone_numbers_correctly(): void
    {
        // Different formats, same number
        $this->assertTrue(
            PhoneNumberNormalizer::areEqual('+49 30 83793369', '+493083793369')
        );

        $this->assertTrue(
            PhoneNumberNormalizer::areEqual('030 83793369', '+493083793369', 'DE')
        );

        // Different numbers
        $this->assertFalse(
            PhoneNumberNormalizer::areEqual('+493083793369', '+491234567890')
        );
    }

    /**
     * Test country code extraction
     */
    public function test_extracts_country_code(): void
    {
        $this->assertEquals(49, PhoneNumberNormalizer::getCountryCode('+493083793369'));
        $this->assertEquals(1, PhoneNumberNormalizer::getCountryCode('+14155552671'));
        $this->assertEquals(43, PhoneNumberNormalizer::getCountryCode('+4366412345678'));
    }

    /**
     * Test region code extraction
     */
    public function test_extracts_region_code(): void
    {
        $this->assertEquals('DE', PhoneNumberNormalizer::getRegionCode('+493083793369'));
        $this->assertEquals('US', PhoneNumberNormalizer::getRegionCode('+14155552671'));
        $this->assertEquals('AT', PhoneNumberNormalizer::getRegionCode('+4366412345678'));
    }

    /**
     * Test display formatting
     */
    public function test_formats_for_display(): void
    {
        $this->assertEquals(
            '+49 30 83793369',
            PhoneNumberNormalizer::formatForDisplay('+493083793369')
        );

        $this->assertEquals(
            '+1 415-555-2671',
            PhoneNumberNormalizer::formatForDisplay('+14155552671')
        );
    }

    /**
     * Test batch normalization
     */
    public function test_normalizes_in_batch(): void
    {
        $input = [
            '+49 30 83793369',
            '030 12345678',
            '+1 415 555 2671',
            null,
            'invalid',
        ];

        $result = PhoneNumberNormalizer::normalizeBatch($input);

        $this->assertEquals('+493083793369', $result[0]);
        $this->assertEquals('+493012345678', $result[1]);
        $this->assertEquals('+14155552671', $result[2]);
        $this->assertNull($result[3]);
        // $result[4] will be null or cleaned depending on implementation
    }

    /**
     * Test backward compatibility with old methods
     */
    public function test_legacy_matches_method(): void
    {
        $this->assertTrue(
            PhoneNumberNormalizer::matches('+49 30 83793369', '+493083793369')
        );
    }

    /**
     * Test edge cases and special inputs
     */
    public function test_handles_edge_cases(): void
    {
        // Whitespace
        $this->assertEquals('+493083793369', PhoneNumberNormalizer::normalize('  +49 30 83793369  '));

        // Anonymous/Unknown
        $this->assertNull(PhoneNumberNormalizer::normalize('anonymous'));
        $this->assertNull(PhoneNumberNormalizer::normalize('unknown'));

        // Empty string
        $this->assertNull(PhoneNumberNormalizer::normalize(''));
        $this->assertNull(PhoneNumberNormalizer::normalize('   '));
    }

    /**
     * Test that normalization is idempotent
     */
    public function test_normalization_is_idempotent(): void
    {
        $phone = '+49 30 83793369';
        $normalized1 = PhoneNumberNormalizer::normalize($phone);
        $normalized2 = PhoneNumberNormalizer::normalize($normalized1);

        $this->assertEquals($normalized1, $normalized2);
    }

    /**
     * Test fallback country codes
     */
    public function test_uses_fallback_countries(): void
    {
        // Austrian number without country code should be detected
        $result = PhoneNumberNormalizer::normalize('066412345678', 'DE');
        $this->assertStringStartsWith('+43', $result); // Austrian country code
    }

    /**
     * Test basic cleaning fallback
     */
    public function test_uses_basic_cleaning_as_fallback(): void
    {
        // Intentionally malformed but cleanable German number
        $result = PhoneNumberNormalizer::normalize('0-30-837-933-69');
        $this->assertStringStartsWith('+49', $result);
        $this->assertEquals('+493083793369', $result);
    }

    // ========== Data Providers ==========

    public static function germanPhoneNumberProvider(): array
    {
        return [
            // [input, expected]
            'Standard format' => ['+493083793369', '+493083793369'],
            'With spaces' => ['+49 30 83793369', '+493083793369'],
            'With dashes' => ['+49-30-83793369', '+493083793369'],
            'National format' => ['030 83793369', '+493083793369'],
            'National with dash' => ['030-83793369', '+493083793369'],
            '0049 format' => ['0049 30 83793369', '+493083793369'],
            'Mobile format' => ['+49 176 12345678', '+4917612345678'],
            'Mobile national' => ['0176 12345678', '+4917612345678'],
            'Service number' => ['0800 1234567', '+498001234567'],
        ];
    }

    public static function internationalPhoneNumberProvider(): array
    {
        return [
            // [input, expected, country]
            'US number' => ['+1 415 555 2671', '+14155552671', 'US'],
            'US national' => ['(415) 555-2671', '+14155552671', 'US'],
            'UK number' => ['+44 20 7946 0958', '+442079460958', 'GB'],
            'UK national' => ['020 7946 0958', '+442079460958', 'GB'],
            'Austrian number' => ['+43 664 12345678', '+4366412345678', 'AT'],
            'Swiss number' => ['+41 44 668 1800', '+41446681800', 'CH'],
            'French number' => ['+33 1 42 86 82 00', '+33142868200', 'FR'],
        ];
    }

    public static function invalidPhoneNumberProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'anonymous' => ['anonymous'],
            'unknown' => ['unknown'],
            'too short' => ['123'],
            'letters only' => ['abcdefg'],
            'special chars only' => ['!!!###'],
        ];
    }

    public static function e164ValidationProvider(): array
    {
        return [
            // [input, expected]
            'Valid E.164' => ['+493083793369', true],
            'Valid US E.164' => ['+14155552671', true],
            'With spaces' => ['+49 30 83793369', false],
            'No plus' => ['493083793369', false],
            'Too short' => ['+49123', false],
            'Too long' => ['+49123456789012345', false],
            'Invalid format' => ['0049 30 83793369', false],
        ];
    }
}