<?php

namespace App\Services\Validation;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PhoneNumberService
{
    private PhoneNumberUtil $phoneUtil;
    private string $defaultRegion;
    private array $supportedRegions = ['DE', 'AT', 'CH']; // Germany, Austria, Switzerland
    
    public function __construct(string $defaultRegion = 'DE')
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $this->defaultRegion = $defaultRegion;
    }
    
    /**
     * Normalize a phone number to E164 format
     * 
     * @param string $phoneNumber The phone number to normalize
     * @param string|null $region The region code (e.g., 'DE', 'AT', 'CH')
     * @return string The normalized phone number in E164 format
     * @throws \InvalidArgumentException If the phone number is invalid
     */
    public function normalize(string $phoneNumber, ?string $region = null): string
    {
        $region = $region ?? $this->defaultRegion;
        
        // Cache normalized numbers for performance
        $cacheKey = "phone_normalized:{$phoneNumber}:{$region}";
        
        return Cache::remember($cacheKey, 3600, function() use ($phoneNumber, $region) {
            try {
                // Pre-process the phone number
                $phoneNumber = $this->preProcessPhoneNumber($phoneNumber);
                
                // Try parsing with the given region
                $number = $this->phoneUtil->parse($phoneNumber, $region);
                
                // Validate the parsed number
                if (!$this->phoneUtil->isValidNumber($number)) {
                    // If invalid, try parsing without region (for international numbers)
                    if (strpos($phoneNumber, '+') === 0) {
                        $number = $this->phoneUtil->parse($phoneNumber, null);
                        
                        if (!$this->phoneUtil->isValidNumber($number)) {
                            throw new \InvalidArgumentException("Invalid phone number: {$phoneNumber}");
                        }
                    } else {
                        throw new \InvalidArgumentException("Invalid phone number: {$phoneNumber}");
                    }
                }
                
                // Additional validation for German numbers
                if ($this->phoneUtil->getRegionCodeForNumber($number) === 'DE') {
                    $this->validateGermanNumber($number);
                }
                
                // Return in E164 format
                return $this->phoneUtil->format($number, PhoneNumberFormat::E164);
                
            } catch (NumberParseException $e) {
                Log::warning("Failed to parse phone number", [
                    'phone' => $phoneNumber,
                    'region' => $region,
                    'error' => $e->getMessage()
                ]);
                throw new \InvalidArgumentException("Cannot parse phone number: {$phoneNumber}");
            }
        });
    }
    
    /**
     * Validate if a phone number is valid
     * 
     * @param string $phoneNumber The phone number to validate
     * @param string|null $region The region code
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $phoneNumber, ?string $region = null): bool
    {
        try {
            $region = $region ?? $this->defaultRegion;
            $phoneNumber = $this->preProcessPhoneNumber($phoneNumber);
            
            // Try parsing with region first
            $number = $this->phoneUtil->parse($phoneNumber, $region);
            
            if (!$this->phoneUtil->isValidNumber($number)) {
                // Try without region for international numbers
                if (strpos($phoneNumber, '+') === 0) {
                    $number = $this->phoneUtil->parse($phoneNumber, null);
                    return $this->phoneUtil->isValidNumber($number);
                }
                return false;
            }
            
            return true;
            
        } catch (NumberParseException $e) {
            return false;
        }
    }
    
    /**
     * Get the region code for a phone number
     * 
     * @param string $phoneNumber The phone number
     * @return string|null The region code or null if not determinable
     */
    public function getRegion(string $phoneNumber): ?string
    {
        try {
            $phoneNumber = $this->preProcessPhoneNumber($phoneNumber);
            
            // Try parsing without region for international numbers
            if (strpos($phoneNumber, '+') === 0) {
                $number = $this->phoneUtil->parse($phoneNumber, null);
            } else {
                // Try with default region
                $number = $this->phoneUtil->parse($phoneNumber, $this->defaultRegion);
            }
            
            return $this->phoneUtil->getRegionCodeForNumber($number);
            
        } catch (NumberParseException $e) {
            return null;
        }
    }
    
    /**
     * Format a phone number in various formats
     * 
     * @param string $phoneNumber The phone number to format
     * @param string $format The format (E164, INTERNATIONAL, NATIONAL, RFC3966)
     * @param string|null $region The region code
     * @return string The formatted phone number
     */
    public function format(string $phoneNumber, string $format = 'E164', ?string $region = null): string
    {
        $region = $region ?? $this->defaultRegion;
        
        try {
            $phoneNumber = $this->preProcessPhoneNumber($phoneNumber);
            $number = $this->phoneUtil->parse($phoneNumber, $region);
            
            if (!$this->phoneUtil->isValidNumber($number)) {
                // Try without region
                if (strpos($phoneNumber, '+') === 0) {
                    $number = $this->phoneUtil->parse($phoneNumber, null);
                }
            }
            
            $formatConstant = match($format) {
                'E164' => PhoneNumberFormat::E164,
                'INTERNATIONAL' => PhoneNumberFormat::INTERNATIONAL,
                'NATIONAL' => PhoneNumberFormat::NATIONAL,
                'RFC3966' => PhoneNumberFormat::RFC3966,
                default => PhoneNumberFormat::E164,
            };
            
            return $this->phoneUtil->format($number, $formatConstant);
            
        } catch (NumberParseException $e) {
            // Return original if parsing fails
            return $phoneNumber;
        }
    }
    
    /**
     * Get the type of phone number (MOBILE, FIXED_LINE, etc.)
     * 
     * @param string $phoneNumber The phone number
     * @param string|null $region The region code
     * @return string|null The phone number type or null if not determinable
     */
    public function getType(string $phoneNumber, ?string $region = null): ?string
    {
        try {
            $region = $region ?? $this->defaultRegion;
            $phoneNumber = $this->preProcessPhoneNumber($phoneNumber);
            $number = $this->phoneUtil->parse($phoneNumber, $region);
            
            $type = $this->phoneUtil->getNumberType($number);
            
            return match($type) {
                PhoneNumberType::MOBILE => 'MOBILE',
                PhoneNumberType::FIXED_LINE => 'FIXED_LINE',
                PhoneNumberType::FIXED_LINE_OR_MOBILE => 'FIXED_LINE_OR_MOBILE',
                PhoneNumberType::TOLL_FREE => 'TOLL_FREE',
                PhoneNumberType::PREMIUM_RATE => 'PREMIUM_RATE',
                PhoneNumberType::VOIP => 'VOIP',
                PhoneNumberType::PERSONAL_NUMBER => 'PERSONAL_NUMBER',
                PhoneNumberType::PAGER => 'PAGER',
                PhoneNumberType::UAN => 'UAN',
                PhoneNumberType::VOICEMAIL => 'VOICEMAIL',
                default => 'UNKNOWN',
            };
            
        } catch (NumberParseException $e) {
            return null;
        }
    }
    
    /**
     * Extract all possible phone numbers from a text
     * 
     * @param string $text The text to search
     * @param string|null $region The region code
     * @return array Array of found phone numbers in E164 format
     */
    public function extractFromText(string $text, ?string $region = null): array
    {
        $region = $region ?? $this->defaultRegion;
        $phoneNumbers = [];
        
        // Regex patterns for common phone number formats
        $patterns = [
            // International format with +
            '/\+[1-9]\d{1,14}/',
            // German formats
            '/(?:0[1-9]\d{1,4}[\s\-\/]?)\d{3,11}/',
            // Numbers in parentheses
            '/\(\d{2,5}\)[\s\-\/]?\d{3,11}/',
            // Numbers with spaces, dashes, dots
            '/\d{2,5}[\s\-\.\/]\d{2,4}[\s\-\.\/]?\d{2,4}[\s\-\.\/]?\d{0,4}/',
        ];
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            
            foreach ($matches[0] as $match) {
                try {
                    $normalized = $this->normalize($match, $region);
                    if (!in_array($normalized, $phoneNumbers)) {
                        $phoneNumbers[] = $normalized;
                    }
                } catch (\Exception $e) {
                    // Skip invalid numbers
                    continue;
                }
            }
        }
        
        return $phoneNumbers;
    }
    
    /**
     * Pre-process phone number before parsing
     * 
     * @param string $phoneNumber The phone number to pre-process
     * @return string The pre-processed phone number
     */
    private function preProcessPhoneNumber(string $phoneNumber): string
    {
        // Trim whitespace
        $phoneNumber = trim($phoneNumber);
        
        // Handle German number formats
        if (!str_starts_with($phoneNumber, '+')) {
            // Replace country code variants
            $phoneNumber = preg_replace('/^00/', '+', $phoneNumber); // 0049 -> +49
            $phoneNumber = preg_replace('/^\+\+/', '+', $phoneNumber); // ++ -> +
        }
        
        // Remove common formatting characters but keep + at the beginning
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Ensure + is only at the beginning
        if (strpos($phoneNumber, '+') > 0) {
            $phoneNumber = str_replace('+', '', $phoneNumber);
        }
        
        return $phoneNumber;
    }
    
    /**
     * Additional validation for German phone numbers
     * 
     * @param PhoneNumber $number The parsed phone number
     * @throws \InvalidArgumentException If the number doesn't meet German requirements
     */
    private function validateGermanNumber(PhoneNumber $number): void
    {
        $nationalNumber = $number->getNationalNumber();
        $nationalNumberStr = (string) $nationalNumber;
        
        // German mobile numbers start with 15, 16, or 17
        if (strlen($nationalNumberStr) >= 2) {
            $prefix = substr($nationalNumberStr, 0, 2);
            $isMobile = in_array($prefix, ['15', '16', '17']);
            
            // Validate mobile number length (typically 11 digits total)
            if ($isMobile && strlen($nationalNumberStr) < 10) {
                throw new \InvalidArgumentException("German mobile number too short");
            }
        }
        
        // Check for premium rate numbers (0900, 0180, etc.)
        if (strlen($nationalNumberStr) >= 4) {
            $prefix = substr($nationalNumberStr, 0, 4);
            $premiumPrefixes = ['0900', '0180', '0137', '0138'];
            
            if (in_array($prefix, $premiumPrefixes)) {
                Log::warning("Premium rate number detected", [
                    'number' => $this->phoneUtil->format($number, PhoneNumberFormat::E164)
                ]);
            }
        }
    }
    
    /**
     * Validate and normalize an array of phone numbers
     * 
     * @param array $phoneNumbers Array of phone numbers
     * @param string|null $region The region code
     * @return array Array of normalized phone numbers (invalid ones excluded)
     */
    public function normalizeMultiple(array $phoneNumbers, ?string $region = null): array
    {
        $normalized = [];
        
        foreach ($phoneNumbers as $phone) {
            try {
                $normalized[] = $this->normalize($phone, $region);
            } catch (\Exception $e) {
                Log::debug("Skipping invalid phone number", ['phone' => $phone]);
                continue;
            }
        }
        
        return array_unique($normalized);
    }
}