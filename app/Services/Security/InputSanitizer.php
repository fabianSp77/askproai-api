<?php

namespace App\Services\Security;

use Illuminate\Support\Str;

class InputSanitizer
{
    /**
     * Sanitize phone number input
     */
    public static function sanitizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters except + at the beginning
        $sanitized = preg_replace('/[^0-9+]/', '', $phone);
        
        // Ensure + is only at the beginning
        if (strpos($sanitized, '+') > 0) {
            $sanitized = str_replace('+', '', $sanitized);
        }
        
        // Validate length (international numbers can be up to 15 digits)
        if (strlen($sanitized) > 20 || strlen($sanitized) < 5) {
            throw new \InvalidArgumentException('Invalid phone number length');
        }
        
        // Validate format
        if (!preg_match('/^\+?[0-9]{5,20}$/', $sanitized)) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }
        
        return $sanitized;
    }

    /**
     * Sanitize email input
     */
    public static function sanitizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        // Convert to lowercase and trim
        $email = strtolower(trim($email));
        
        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
        
        // Additional security checks
        if (preg_match('/[<>\"\'%;()&\\\\]/', $email)) {
            throw new \InvalidArgumentException('Email contains invalid characters');
        }
        
        // Check length
        if (strlen($email) > 255) {
            throw new \InvalidArgumentException('Email too long');
        }
        
        return $email;
    }

    /**
     * Sanitize search input to prevent SQL injection
     */
    public static function sanitizeSearchQuery(?string $query): string
    {
        if (!$query) {
            return '';
        }

        // Remove SQL keywords and special characters
        $dangerous = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'UNION',
            'WHERE', 'FROM', 'JOIN', 'ORDER BY', 'GROUP BY',
            '--', '/*', '*/', 'xp_', 'sp_', '@@', '@',
            'EXEC', 'EXECUTE', 'CAST', 'DECLARE', 'NVARCHAR',
            'VARCHAR', 'CHAR', 'ALTER', 'CREATE', 'SHUTDOWN',
            'GRANT', 'REVOKE', 'DENY', 'SET', 'SCRIPT',
            '<', '>', '"', '\'', ';', '\\', '\0', '\n', '\r',
            '\x1a', '%00', '%0a', '%0d', '%1a', '%22', '%27', '%3c', '%3e'
        ];
        
        $sanitized = str_ireplace($dangerous, '', $query);
        
        // Remove multiple spaces
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        
        // Trim and limit length
        $sanitized = substr(trim($sanitized), 0, 100);
        
        return $sanitized;
    }

    /**
     * Sanitize HTML content to prevent XSS
     */
    public static function sanitizeHtml(?string $html): string
    {
        if (!$html) {
            return '';
        }

        // Use HTMLPurifier or similar library in production
        // For now, basic sanitization
        $allowed_tags = '<p><br><strong><em><u><a><ul><ol><li><blockquote>';
        $sanitized = strip_tags($html, $allowed_tags);
        
        // Remove JavaScript event handlers
        $sanitized = preg_replace('/on[a-zA-Z]+\s*=\s*["\'][^"\']*["\']/', '', $sanitized);
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        
        return $sanitized;
    }

    /**
     * Sanitize filename for uploads
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Get extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Sanitize name
        $name = Str::slug($name);
        
        // Limit length
        $name = substr($name, 0, 100);
        
        // Validate extension against whitelist
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array(strtolower($ext), $allowed_extensions)) {
            throw new \InvalidArgumentException('File type not allowed');
        }
        
        return $name . '.' . $ext;
    }

    /**
     * Sanitize JSON input
     */
    public static function sanitizeJson(?string $json): ?array
    {
        if (!$json) {
            return null;
        }

        // Decode JSON
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON format');
        }
        
        // Recursively sanitize all string values
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Encode special characters
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });
        
        return $data;
    }

    /**
     * Validate and sanitize ID parameters
     */
    public static function sanitizeId($id): int
    {
        if (!is_numeric($id) || $id < 1) {
            throw new \InvalidArgumentException('Invalid ID parameter');
        }
        
        return (int) $id;
    }

    /**
     * Sanitize URL input
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }
        
        // Check protocol
        $allowed_protocols = ['http', 'https'];
        $parsed = parse_url($url);
        
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], $allowed_protocols)) {
            throw new \InvalidArgumentException('Invalid URL protocol');
        }
        
        // Prevent JavaScript URLs
        if (stripos($url, 'javascript:') !== false) {
            throw new \InvalidArgumentException('JavaScript URLs not allowed');
        }
        
        return $url;
    }

    /**
     * Sanitize date input
     */
    public static function sanitizeDate(?string $date, string $format = 'Y-m-d'): ?string
    {
        if (!$date) {
            return null;
        }

        $d = \DateTime::createFromFormat($format, $date);
        
        if (!$d || $d->format($format) !== $date) {
            throw new \InvalidArgumentException('Invalid date format');
        }
        
        // Check reasonable date range (e.g., not more than 100 years in past/future)
        $now = new \DateTime();
        $diff = $now->diff($d);
        
        if ($diff->y > 100) {
            throw new \InvalidArgumentException('Date out of reasonable range');
        }
        
        return $date;
    }

    /**
     * Sanitize numeric input
     */
    public static function sanitizeNumeric($value, float $min = null, float $max = null): float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Value must be numeric');
        }
        
        $value = (float) $value;
        
        if ($min !== null && $value < $min) {
            throw new \InvalidArgumentException("Value must be at least {$min}");
        }
        
        if ($max !== null && $value > $max) {
            throw new \InvalidArgumentException("Value must be at most {$max}");
        }
        
        return $value;
    }
}