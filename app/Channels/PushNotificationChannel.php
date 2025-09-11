<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationChannel
{
    /**
     * Send the given notification via push.
     */
    public function send($notifiable, Notification $notification)
    {
        // Get push tokens for the user
        $tokens = $this->getPushTokens($notifiable);
        
        if (empty($tokens)) {
            return;
        }
        
        // Get the push representation of the notification
        $data = $notification->toPush($notifiable);
        
        // Send to each token
        foreach ($tokens as $token) {
            $this->sendPushNotification($token, $data);
        }
    }
    
    /**
     * Get push tokens for a notifiable
     */
    protected function getPushTokens($notifiable): array
    {
        // Get tokens from database
        $tokens = \DB::table('push_subscriptions')
            ->where('user_id', $notifiable->id)
            ->where('active', true)
            ->pluck('token')
            ->toArray();
        
        return $tokens;
    }
    
    /**
     * Send push notification via service
     */
    protected function sendPushNotification(string $token, array $data): void
    {
        try {
            // Using Web Push Protocol for browser notifications
            $payload = [
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'icon' => $data['icon'] ?? '/icon-192x192.png',
                    'badge' => '/badge-72x72.png',
                    'tag' => $data['tag'] ?? uniqid(),
                    'requireInteraction' => $data['requireInteraction'] ?? false,
                    'actions' => $data['actions'] ?? [],
                    'data' => [
                        'url' => $data['url'] ?? '/',
                        'metadata' => $data['data'] ?? []
                    ]
                ]
            ];
            
            // Send via Web Push service
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'TTL' => 60 * 60 * 24 // 24 hours
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => $payload['notification']
            ]);
            
            if (!$response->successful()) {
                Log::warning('Push notification failed', [
                    'token' => substr($token, 0, 20) . '...',
                    'response' => $response->json()
                ]);
                
                // Mark token as invalid if it's expired
                if ($response->status() === 410) {
                    $this->invalidateToken($token);
                }
            }
        } catch (\Exception $e) {
            Log::error('Push notification error', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
        }
    }
    
    /**
     * Invalidate an expired token
     */
    protected function invalidateToken(string $token): void
    {
        \DB::table('push_subscriptions')
            ->where('token', $token)
            ->update(['active' => false, 'invalidated_at' => now()]);
    }
}