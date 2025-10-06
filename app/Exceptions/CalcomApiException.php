<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Cal.com API Exception
 *
 * Thrown when Cal.com API requests fail or return errors.
 * Helps distinguish between "no availability" and "API down" scenarios.
 */
class CalcomApiException extends Exception
{
    protected ?Response $response;
    protected string $calcomEndpoint;
    protected array $requestParams;

    public function __construct(
        string $message,
        ?Response $response = null,
        string $endpoint = '',
        array $params = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->response = $response;
        $this->calcomEndpoint = $endpoint;
        $this->requestParams = $params;
    }

    /**
     * Get the HTTP response if available
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get the Cal.com endpoint that failed
     */
    public function getEndpoint(): string
    {
        return $this->calcomEndpoint;
    }

    /**
     * Get the request parameters
     */
    public function getRequestParams(): array
    {
        return $this->requestParams;
    }

    /**
     * Get HTTP status code if response available
     */
    public function getStatusCode(): ?int
    {
        return $this->response?->status();
    }

    /**
     * Get error details for logging
     */
    public function getErrorDetails(): array
    {
        return [
            'message' => $this->getMessage(),
            'endpoint' => $this->calcomEndpoint,
            'params' => $this->requestParams,
            'status_code' => $this->getStatusCode(),
            'response_body' => $this->response?->body(),
        ];
    }

    /**
     * Create exception from failed HTTP response
     */
    public static function fromResponse(
        Response $response,
        string $endpoint,
        array $params = [],
        string $httpMethod = 'GET'
    ): self {
        $statusCode = $response->status();
        $message = sprintf(
            'Cal.com API request failed: %s %s (HTTP %d)',
            strtoupper($httpMethod),
            $endpoint,
            $statusCode
        );

        // Try to get error message from response
        $responseData = $response->json();
        if (isset($responseData['message'])) {
            $message .= ' - ' . (is_array($responseData['message']) ? json_encode($responseData['message']) : $responseData['message']);
        } elseif (isset($responseData['error'])) {
            $message .= ' - ' . (is_array($responseData['error']) ? json_encode($responseData['error']) : $responseData['error']);
        }

        return new self(
            $message,
            $response,
            $endpoint,
            $params,
            $statusCode
        );
    }

    /**
     * Create exception from network error/timeout
     */
    public static function networkError(
        string $endpoint,
        array $params = [],
        ?Exception $previous = null
    ): self {
        $message = sprintf(
            'Cal.com API network error: Unable to reach %s',
            $endpoint
        );

        return new self(
            $message,
            null,
            $endpoint,
            $params,
            0,
            $previous
        );
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        $statusCode = $this->getStatusCode();

        return match(true) {
            $statusCode === 401 || $statusCode === 403 =>
                'Terminbuchungssystem: Authentifizierungsfehler. Bitte kontaktieren Sie den Support.',

            $statusCode === 404 =>
                'Terminbuchungssystem: Angeforderte Ressource nicht gefunden.',

            $statusCode === 429 =>
                'Terminbuchungssystem: Zu viele Anfragen. Bitte versuchen Sie es in wenigen Minuten erneut.',

            $statusCode >= 500 =>
                'Terminbuchungssystem ist momentan nicht verfügbar. Bitte versuchen Sie es später erneut.',

            $statusCode === null =>
                'Terminbuchungssystem ist nicht erreichbar. Bitte überprüfen Sie Ihre Internetverbindung.',

            default =>
                'Terminbuchungssystem hat einen Fehler zurückgegeben. Bitte versuchen Sie es erneut.'
        };
    }
}
