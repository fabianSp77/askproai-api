<?php

namespace App\Services\Notifications\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\NotificationQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportException;

class EmailChannel implements NotificationChannelInterface
{
    protected array $config;
    protected string $fromAddress;
    protected string $fromName;
    protected bool $trackOpens = true;
    protected bool $trackClicks = true;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'reply_to' => config('mail.reply_to.address'),
            'track_opens' => true,
            'track_clicks' => true,
            'categories' => [],
            'unsubscribe_url' => null,
            'custom_headers' => []
        ], $config);

        $this->fromAddress = $this->config['from_address'];
        $this->fromName = $this->config['from_name'];
        $this->trackOpens = $this->config['track_opens'];
        $this->trackClicks = $this->config['track_clicks'];
    }

    public function send(NotificationQueue $notification): array
    {
        try {
            $recipient = $notification->recipient;
            $data = $notification->data;

            // Validate recipient
            if (!$this->validateRecipient($recipient)) {
                return $this->failureResponse('Invalid email address');
            }

            // Build email message
            $message = $this->buildMessage($notification);

            // Send email
            Mail::raw($message['text'] ?? '', function ($mail) use ($message, $recipient) {
                $mail->to($recipient['email'], $recipient['name'] ?? null)
                    ->subject($message['subject']);

                // Set from address
                if (isset($message['from'])) {
                    $mail->from($message['from']['address'], $message['from']['name']);
                }

                // Set reply-to
                if (isset($message['reply_to'])) {
                    $mail->replyTo($message['reply_to']);
                }

                // Add HTML version
                if (isset($message['html'])) {
                    $mail->html($message['html']);
                }

                // Add attachments
                if (!empty($message['attachments'])) {
                    foreach ($message['attachments'] as $attachment) {
                        $mail->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }

                // Add custom headers
                if (!empty($message['headers'])) {
                    foreach ($message['headers'] as $header => $value) {
                        $mail->getHeaders()->addTextHeader($header, $value);
                    }
                }

                // Add tracking pixels and link rewriting if enabled
                if ($this->trackOpens) {
                    $mail->getHeaders()->addTextHeader(
                        'X-Track-Opens',
                        $this->generateTrackingId($notification)
                    );
                }

                if ($this->trackClicks) {
                    $mail->getHeaders()->addTextHeader(
                        'X-Track-Clicks',
                        'true'
                    );
                }

                // Add unsubscribe header
                if (isset($message['unsubscribe_url'])) {
                    $mail->getHeaders()->addTextHeader(
                        'List-Unsubscribe',
                        '<' . $message['unsubscribe_url'] . '>'
                    );
                    $mail->getHeaders()->addTextHeader(
                        'List-Unsubscribe-Post',
                        'List-Unsubscribe=One-Click'
                    );
                }

                // Add categories for analytics
                if (!empty($message['categories'])) {
                    $mail->getHeaders()->addTextHeader(
                        'X-Categories',
                        json_encode($message['categories'])
                    );
                }
            });

            // Get message ID from mail provider
            $messageId = $this->getLastMessageId();

            return $this->successResponse([
                'message_id' => $messageId,
                'accepted' => true,
                'envelope' => [
                    'from' => $this->fromAddress,
                    'to' => [$recipient['email']]
                ]
            ]);

        } catch (TransportException $e) {
            Log::error('Email transport error', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return $this->failureResponse(
                'Transport error: ' . $e->getMessage(),
                ['code' => $e->getCode()]
            );

        } catch (\Exception $e) {
            Log::error('Email send error', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            return $this->failureResponse('Failed to send email: ' . $e->getMessage());
        }
    }

    public function sendBulk(array $notifications): array
    {
        $results = [];

        // Use mail queue for better performance
        foreach ($notifications as $notification) {
            // Queue the email instead of sending immediately
            $results[] = $this->queueEmail($notification);
        }

        return $results;
    }

    protected function buildMessage(NotificationQueue $notification): array
    {
        $data = $notification->data;
        $message = [];

        // Basic fields
        $message['subject'] = $data['subject'] ?? 'Notification';
        $message['text'] = $data['content'] ?? '';

        // HTML content
        if (isset($data['html'])) {
            $message['html'] = $this->processHtmlContent($data['html'], $notification);
        } elseif (isset($data['content'])) {
            // Convert plain text to basic HTML
            $message['html'] = $this->convertTextToHtml($data['content']);
        }

        // From address
        if (isset($data['from'])) {
            $message['from'] = [
                'address' => $data['from']['address'] ?? $this->fromAddress,
                'name' => $data['from']['name'] ?? $this->fromName
            ];
        } else {
            $message['from'] = [
                'address' => $this->fromAddress,
                'name' => $this->fromName
            ];
        }

        // Reply-to
        if (isset($data['reply_to'])) {
            $message['reply_to'] = $data['reply_to'];
        } elseif ($this->config['reply_to']) {
            $message['reply_to'] = $this->config['reply_to'];
        }

        // Attachments
        if (!empty($data['attachments'])) {
            $message['attachments'] = $data['attachments'];
        }

        // Headers
        $message['headers'] = array_merge(
            $this->config['custom_headers'] ?? [],
            $data['headers'] ?? [],
            [
                'X-Notification-ID' => $notification->uuid,
                'X-Notification-Type' => $notification->type,
                'X-Priority' => $this->mapPriority($notification->priority)
            ]
        );

        // Categories for tracking
        $message['categories'] = array_merge(
            $this->config['categories'] ?? [],
            [$notification->type, $notification->channel]
        );

        // Unsubscribe URL
        if ($this->config['unsubscribe_url']) {
            $message['unsubscribe_url'] = str_replace(
                '{token}',
                $this->generateUnsubscribeToken($notification),
                $this->config['unsubscribe_url']
            );
        }

        return $message;
    }

    protected function processHtmlContent(string $html, NotificationQueue $notification): string
    {
        // Add tracking pixel
        if ($this->trackOpens) {
            $trackingPixel = sprintf(
                '<img src="%s/api/notifications/track/open/%s" width="1" height="1" style="display:none;" />',
                config('app.url'),
                $notification->uuid
            );
            $html = str_replace('</body>', $trackingPixel . '</body>', $html);
        }

        // Rewrite links for click tracking
        if ($this->trackClicks) {
            $html = preg_replace_callback(
                '/<a([^>]*)href="([^"]+)"([^>]*)>/i',
                function ($matches) use ($notification) {
                    $attributes = $matches[1] . $matches[3];
                    $url = $matches[2];

                    // Skip mailto and tel links
                    if (str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
                        return $matches[0];
                    }

                    $trackedUrl = sprintf(
                        '%s/api/notifications/track/click/%s?url=%s',
                        config('app.url'),
                        $notification->uuid,
                        urlencode($url)
                    );

                    return sprintf('<a%shref="%s"%s>', $attributes, $trackedUrl, $attributes);
                },
                $html
            );
        }

        // Add unsubscribe link in footer
        if (isset($message['unsubscribe_url'])) {
            $unsubscribeHtml = sprintf(
                '<div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #666;">
                    <a href="%s" style="color: #666; text-decoration: underline;">Abmelden</a> |
                    <a href="%s/preferences" style="color: #666; text-decoration: underline;">Einstellungen verwalten</a>
                </div>',
                $message['unsubscribe_url'],
                config('app.url')
            );
            $html = str_replace('</body>', $unsubscribeHtml . '</body>', $html);
        }

        return $html;
    }

    protected function convertTextToHtml(string $text): string
    {
        // Basic text to HTML conversion
        $html = nl2br(htmlspecialchars($text));

        // Wrap in basic HTML structure
        return sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px;">
                <div style="max-width: 600px; margin: 0 auto;">
                    %s
                </div>
            </body>
            </html>',
            $html
        );
    }

    protected function queueEmail(NotificationQueue $notification): array
    {
        try {
            // Add to Laravel's mail queue
            dispatch(function () use ($notification) {
                $this->send($notification);
            })->onQueue('emails')->delay(now()->addSeconds(5));

            return $this->successResponse([
                'queued' => true,
                'queue_id' => $notification->uuid
            ]);
        } catch (\Exception $e) {
            return $this->failureResponse('Failed to queue email: ' . $e->getMessage());
        }
    }

    protected function validateRecipient(array $recipient): bool
    {
        if (!isset($recipient['email'])) {
            return false;
        }

        return filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function mapPriority(int $priority): string
    {
        return match (true) {
            $priority <= 2 => 'high',
            $priority <= 5 => 'normal',
            default => 'low'
        };
    }

    protected function generateTrackingId(NotificationQueue $notification): string
    {
        return base64_encode($notification->uuid . ':' . time());
    }

    protected function generateUnsubscribeToken(NotificationQueue $notification): string
    {
        return hash_hmac(
            'sha256',
            $notification->notifiable_id . ':' . $notification->channel,
            config('app.key')
        );
    }

    protected function getLastMessageId(): ?string
    {
        // This would need to be implemented based on the mail driver
        // For now, return a generated ID
        return 'email_' . uniqid();
    }

    protected function successResponse(array $data = []): array
    {
        return [
            'success' => true,
            'channel' => 'email',
            'data' => $data
        ];
    }

    protected function failureResponse(string $error, array $data = []): array
    {
        return [
            'success' => false,
            'channel' => 'email',
            'error' => $error,
            'data' => $data
        ];
    }

    public function validateConfig(): bool
    {
        return !empty($this->config['from_address']) &&
               !empty($this->config['from_name']);
    }

    public function getChannelName(): string
    {
        return 'email';
    }

    public function supportsTemplates(): bool
    {
        return true;
    }

    public function supportsBulkSending(): bool
    {
        return true;
    }

    public function getMaxRecipientsPerRequest(): int
    {
        return 1000; // Can be adjusted based on mail provider limits
    }

    public function estimateCost(int $messageCount): float
    {
        // Email is typically free or has a very low cost
        // This can be customized based on provider
        return 0.0;
    }

    public function canDeliver(array $recipient): bool
    {
        // Check if recipient has valid email address
        if (empty($recipient['email'])) {
            return false;
        }

        // Validate email format
        if (!filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Check if channel is properly configured
        if (!$this->validateConfig()) {
            return false;
        }

        return true;
    }
}