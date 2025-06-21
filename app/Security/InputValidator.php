<?php

namespace App\Security;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

class InputValidator
{
    private array $sqlInjectionPatterns = [
        '/(\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|vbscript)\b)/i',
        '/(\b(or|and)\b\s*\d+\s*=\s*\d+)/i',
        '/(--|\#|\/\*|\*\/)/i',
        '/(\b(xp_|sp_)\w+)/i',
        '/(;\s*(drop|delete|truncate|update|insert))/i',
    ];
    
    private array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>/i',
        '/on\w+\s*=\s*["\']?[^"\']*["\']?/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
    ];
    
    private array $pathTraversalPatterns = [
        '/\.\.\//',
        '/\.\.\\\\/',
        '/%2e%2e%2f/i',
        '/%2e%2e%5c/i',
        '/\.\.[\/\\\\]/',
    ];
    
    /**
     * Check if input contains SQL injection patterns
     */
    public function containsSqlInjection(string $input): bool
    {
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        // Check for encoded characters that might be SQL injection
        $decoded = urldecode($input);
        if ($decoded !== $input) {
            return $this->containsSqlInjection($decoded);
        }
        
        return false;
    }
    
    /**
     * Check if input contains XSS patterns
     */
    public function containsXss(string $input): bool
    {
        // First check for HTML tags
        if ($input !== strip_tags($input)) {
            // Allow some safe tags for rich text fields
            $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><blockquote>';
            $stripped = strip_tags($input, $allowedTags);
            
            // If there are still differences, check for dangerous patterns
            if ($input !== $stripped) {
                return true;
            }
        }
        
        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        // Check for encoded XSS
        $decoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5);
        if ($decoded !== $input) {
            return $this->containsXss($decoded);
        }
        
        return false;
    }
    
    /**
     * Check if input contains path traversal patterns
     */
    public function containsPathTraversal(string $input): bool
    {
        foreach ($this->pathTraversalPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate phone number format
     */
    public function isValidPhoneNumber(string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }
        
        // Basic validation for common formats
        $basicPattern = '/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,5}[-\s\.]?[0-9]{1,5}$/';
        if (!preg_match($basicPattern, $phone)) {
            return false;
        }
        
        // Try to use libphonenumber if available
        if (class_exists(PhoneNumberUtil::class)) {
            try {
                $phoneUtil = PhoneNumberUtil::getInstance();
                $numberProto = $phoneUtil->parse($phone, 'DE'); // Default to Germany
                return $phoneUtil->isValidNumber($numberProto);
            } catch (NumberParseException $e) {
                // Fall back to basic validation
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Validate date/time format
     */
    public function isValidDateTime(string $datetime): bool
    {
        // Check common formats
        $formats = [
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:s\Z',
            'Y-m-d\TH:i:sP',
            'c', // ISO 8601
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $datetime);
            if ($date && $date->format($format) === $datetime) {
                return true;
            }
        }
        
        // Try to parse with strtotime as fallback
        $timestamp = strtotime($datetime);
        return $timestamp !== false && $timestamp > 0;
    }
    
    /**
     * Sanitize HTML content while preserving allowed tags
     */
    public function sanitizeHtml(string $html, array $allowedTags = []): string
    {
        if (empty($allowedTags)) {
            $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li', 'blockquote'];
        }
        
        // Convert array to string format for strip_tags
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        
        // Strip tags except allowed ones
        $cleaned = strip_tags($html, $allowedTagsString);
        
        // Remove dangerous attributes from allowed tags
        $cleaned = preg_replace('/(<[^>]+)\s+on\w+\s*=\s*["\']?[^"\']*["\']?/i', '$1', $cleaned);
        $cleaned = preg_replace('/(<[^>]+)\s+style\s*=\s*["\']?[^"\']*["\']?/i', '$1', $cleaned);
        
        return $cleaned;
    }
    
    /**
     * Validate and sanitize file paths
     */
    public function sanitizeFilePath(string $path): string
    {
        // Remove any path traversal attempts
        $path = str_replace(['../', '..\\', '..', '%2e%2e'], '', $path);
        
        // Remove null bytes
        $path = str_replace(chr(0), '', $path);
        
        // Normalize slashes
        $path = str_replace('\\', '/', $path);
        
        // Remove double slashes
        $path = preg_replace('#/+#', '/', $path);
        
        return $path;
    }
}