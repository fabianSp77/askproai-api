<?php

namespace App\Services\Validation;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PhoneNumberValidator
{
    private PhoneNumberUtil $phoneUtil;
    private array $supportedCountries;
    private string $defaultCountry;
    
    // Cache settings
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'phone_validation:';
    
    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $this->defaultCountry = config('services.phone.default_country', 'DE');
        $this->supportedCountries = config('services.phone.supported_countries', ['DE', 'AT', 'CH']);
    }
    
    /**
     * Validate and normalize a phone number
     */
    public function validate(string $phoneNumber, string $country = null): array
    {
        // Sanitize input to prevent SQL injection
        $phoneNumber = $this->sanitizeInput($phoneNumber);
        $country = $country ? $this->sanitizeCountryCode($country) : $this->defaultCountry;
        
        // Check cache first
        $cacheKey = self::CACHE_PREFIX . md5($phoneNumber . ':' . $country);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Parse the phone number
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, $country);
            
            // Validate the number
            $isValid = $this->phoneUtil->isValidNumber($parsedNumber);
            
            if (!$isValid) {
                $result = [
                    'valid' => false,
                    'error' => 'Invalid phone number format',
                    'original' => $phoneNumber,
                ];
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                return $result;
            }
            
            // Check if it's a possible number
            $isPossible = $this->phoneUtil->isPossibleNumber($parsedNumber);
            
            // Get number type
            $numberType = $this->phoneUtil->getNumberType($parsedNumber);
            
            // Check if country is supported
            $numberCountry = $this->phoneUtil->getRegionCodeForNumber($parsedNumber);
            $isSupported = in_array($numberCountry, $this->supportedCountries);
            
            // Format the number in different formats
            $result = [
                'valid' => true,
                'possible' => $isPossible,
                'supported_country' => $isSupported,
                'country' => $numberCountry,
                'type' => $this->getNumberTypeString($numberType),
                'original' => $phoneNumber,
                'normalized' => $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::E164),
                'international' => $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::INTERNATIONAL),
                'national' => $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::NATIONAL),
                'carrier' => $this->getCarrierInfo($parsedNumber),
            ];
            
            // Log validation for monitoring
            if (!$isSupported) {
                Log::warning('Phone number from unsupported country', [
                    'number' => $result['normalized'],
                    'country' => $numberCountry,
                ]);
            }
            
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
            
        } catch (NumberParseException $e) {
            $result = [
                'valid' => false,
                'error' => $this->getParseErrorMessage($e),
                'original' => $phoneNumber,
                'error_code' => $e->getErrorType(),
            ];
            
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        }
    }
    
    /**
     * Validate phone number for database storage
     */
    public function validateForStorage(string $phoneNumber, string $country = null): ?string
    {
        $validation = $this->validate($phoneNumber, $country);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                "Invalid phone number: " . ($validation['error'] ?? 'Unknown error')
            );
        }
        
        // Return E164 format for database storage
        return $validation['normalized'];
    }
    
    /**
     * Check if phone number is mobile
     */
    public function isMobile(string $phoneNumber, string $country = null): bool
    {
        $validation = $this->validate($phoneNumber, $country);
        
        if (!$validation['valid']) {
            return false;
        }
        
        return in_array($validation['type'], ['MOBILE', 'FIXED_LINE_OR_MOBILE']);
    }
    
    /**
     * Sanitize phone number input to prevent SQL injection
     */
    private function sanitizeInput(string $input): string
    {
        // Remove all non-numeric characters except + and spaces
        $sanitized = preg_replace('/[^0-9+\s\-\(\)]/', '', $input);
        
        // Limit length to prevent buffer overflow
        $sanitized = substr($sanitized, 0, 50);
        
        // Additional validation - must start with + or digit
        if (!preg_match('/^[\+0-9]/', $sanitized)) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize country code
     */
    private function sanitizeCountryCode(string $country): string
    {
        // Country code must be exactly 2 uppercase letters
        $sanitized = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $country), 0, 2));
        
        if (strlen($sanitized) !== 2) {
            throw new \InvalidArgumentException('Invalid country code');
        }
        
        return $sanitized;
    }
    
    /**
     * Get human-readable number type
     */
    private function getNumberTypeString($type): string
    {
        // Handle PhoneNumberType enum object
        if (is_object($type)) {
            // Try to get the enum name directly
            if (method_exists($type, 'name')) {
                return $type->name();
            }
            // For older versions, use the class constants
            $className = get_class($type);
            $reflection = new \ReflectionClass($className);
            foreach ($reflection->getConstants() as $name => $value) {
                if ($value === $type) {
                    return $name;
                }
            }
            // As fallback, try to cast to string
            return (string) $type;
        }
        
        // Handle integer values (legacy)
        $types = [
            0 => 'FIXED_LINE',
            1 => 'MOBILE',
            2 => 'FIXED_LINE_OR_MOBILE',
            3 => 'TOLL_FREE',
            4 => 'PREMIUM_RATE',
            5 => 'SHARED_COST',
            6 => 'VOIP',
            7 => 'PERSONAL_NUMBER',
            8 => 'PAGER',
            9 => 'UAN',
            10 => 'VOICEMAIL',
            -1 => 'UNKNOWN'
        ];
        
        return $types[$type] ?? 'UNKNOWN';
    }
    
    /**
     * Get carrier information (placeholder - requires additional service)
     */
    private function getCarrierInfo(PhoneNumber $number): ?array
    {
        // This would require integration with a carrier lookup service
        // For now, return null
        return null;
    }
    
    /**
     * Get user-friendly parse error message
     */
    private function getParseErrorMessage(NumberParseException $e): string
    {
        switch ($e->getErrorType()) {
            case NumberParseException::INVALID_COUNTRY_CODE:
                return 'Invalid country code';
            case NumberParseException::NOT_A_NUMBER:
                return 'The input is not a valid phone number';
            case NumberParseException::TOO_SHORT_NSN:
                return 'The phone number is too short';
            case NumberParseException::TOO_SHORT_AFTER_IDD:
                return 'The phone number is too short after the country code';
            case NumberParseException::TOO_LONG:
                return 'The phone number is too long';
            default:
                return 'Invalid phone number format';
        }
    }
    
    /**
     * Batch validate multiple phone numbers
     */
    public function validateBatch(array $phoneNumbers, string $country = null): array
    {
        $results = [];
        
        foreach ($phoneNumbers as $key => $phoneNumber) {
            $results[$key] = $this->validate($phoneNumber, $country);
        }
        
        return $results;
    }
    
    /**
     * Check if two phone numbers are the same
     */
    public function isSameNumber(string $phone1, string $phone2, string $country = null): bool
    {
        $validation1 = $this->validate($phone1, $country);
        $validation2 = $this->validate($phone2, $country);
        
        if (!$validation1['valid'] || !$validation2['valid']) {
            return false;
        }
        
        return $validation1['normalized'] === $validation2['normalized'];
    }
    
    /**
     * Get example number for a country
     */
    public function getExampleNumber(string $country = null): ?string
    {
        $country = $country ?? $this->defaultCountry;
        
        try {
            $example = $this->phoneUtil->getExampleNumber($country);
            if ($example) {
                return $this->phoneUtil->format($example, PhoneNumberFormat::INTERNATIONAL);
            }
        } catch (\Exception $e) {
            Log::error('Failed to get example number', [
                'country' => $country,
                'error' => $e->getMessage(),
            ]);
        }
        
        return null;
    }
    
    /**
     * Clear validation cache
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info('Phone validation cache cleared');
    }
}