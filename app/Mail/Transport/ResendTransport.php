<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class ResendTransport extends AbstractTransport
{
    protected $apiKey;
    protected $apiUrl = 'https://api.resend.com/emails';

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        \Illuminate\Support\Facades\Log::info('[ResendTransport] Sending email', [
            'from' => $email->getFrom()[0]->toString(),
            'to' => array_map(fn($a) => $a->toString(), $email->getTo()),
            'subject' => $email->getSubject()
        ]);
        
        $payload = [
            'from' => $this->formatAddress($email->getFrom()[0]),
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject(),
        ];

        // Add optional fields
        if ($email->getCc()) {
            $payload['cc'] = $this->formatAddresses($email->getCc());
        }
        
        if ($email->getBcc()) {
            $payload['bcc'] = $this->formatAddresses($email->getBcc());
        }
        
        if ($email->getReplyTo()) {
            $payload['reply_to'] = $this->formatAddress($email->getReplyTo()[0]);
        }

        // Handle content
        if ($email->getHtmlBody()) {
            $payload['html'] = $email->getHtmlBody();
        }
        
        if ($email->getTextBody()) {
            $payload['text'] = $email->getTextBody();
        }

        // Handle attachments
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            if ($attachment instanceof DataPart) {
                $attachments[] = [
                    'filename' => $attachment->getFilename(),
                    'content' => base64_encode($attachment->getBody()),
                ];
            }
        }
        
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        // Send via Resend API
        $response = $this->sendRequest($payload);
        
        \Illuminate\Support\Facades\Log::info('[ResendTransport] API Response', [
            'success' => $response['success'],
            'data' => $response['data'] ?? null,
            'error' => $response['error'] ?? null
        ]);
        
        if (!$response['success']) {
            throw new \Exception('Resend API Error: ' . ($response['error'] ?? 'Unknown error'));
        }
    }

    protected function sendRequest(array $payload): array
    {
        $ch = curl_init($this->apiUrl);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL Error: ' . $error];
        }

        $data = json_decode($response, true);

        if ($statusCode >= 200 && $statusCode < 300) {
            return ['success' => true, 'data' => $data];
        }

        return [
            'success' => false,
            'error' => $data['message'] ?? $data['error'] ?? 'HTTP ' . $statusCode,
        ];
    }

    protected function formatAddress($address): string
    {
        if (is_string($address)) {
            return $address;
        }
        
        if ($address->getName()) {
            return sprintf('%s <%s>', $address->getName(), $address->getAddress());
        }
        
        return $address->getAddress();
    }

    protected function formatAddresses(array $addresses): array
    {
        return array_map([$this, 'formatAddress'], $addresses);
    }

    public function __toString(): string
    {
        return 'resend';
    }
}