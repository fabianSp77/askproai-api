<?php

namespace App\Exceptions;

use Exception;
use App\Services\Security\SensitiveDataMasker;

class SafeApiException extends Exception
{
    private SensitiveDataMasker $masker;
    
    public function __construct(string $message, int $code = 0, ?Exception $previous = null)
    {
        $this->masker = new SensitiveDataMasker();
        
        // Mask sensitive data in the message
        $safeMessage = $this->masker->mask($message);
        
        parent::__construct($safeMessage, $code, $previous);
    }
    
    /**
     * Create exception with masked context
     */
    public static function withContext(string $message, array $context = [], int $code = 0): self
    {
        $masker = new SensitiveDataMasker();
        $safeContext = $masker->createSafeContext($context);
        
        // Add context to message in a safe way
        $contextString = json_encode($safeContext, JSON_PRETTY_PRINT);
        $fullMessage = $message . "\nContext: " . $contextString;
        
        return new self($fullMessage, $code);
    }
    
    /**
     * Create from API response
     */
    public static function fromApiResponse(string $service, string $method, $response, ?string $customMessage = null): self
    {
        $masker = new SensitiveDataMasker();
        
        $message = $customMessage ?? "{$service} API call failed";
        
        if (is_object($response) && method_exists($response, 'status')) {
            $message .= " (Status: {$response->status()})";
        }
        
        if (is_object($response) && method_exists($response, 'body')) {
            // Limit body size and mask sensitive data
            $body = substr($response->body(), 0, 500);
            $safeBody = $masker->mask($body);
            $message .= "\nResponse: {$safeBody}";
        }
        
        return new self($message);
    }
    
    /**
     * Convert any exception to safe exception
     */
    public static function from(\Throwable $e): self
    {
        $masker = new SensitiveDataMasker();
        $safeMessage = $masker->mask($e->getMessage());
        
        return new self($safeMessage, $e->getCode(), $e);
    }
}