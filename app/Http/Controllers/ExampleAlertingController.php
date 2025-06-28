<?php

namespace App\Http\Controllers;

use App\Services\Monitoring\UnifiedAlertingService;
use App\Services\Notifications\SlackNotificationService;
use App\Traits\TracksPaymentFailures;
use App\Traits\TracksWebhookFailures;
use Illuminate\Http\Request;

/**
 * Example controller demonstrating alerting system usage.
 */
class ExampleAlertingController extends Controller
{
    use TracksPaymentFailures;
    use TracksWebhookFailures;

    private UnifiedAlertingService $alertingService;

    private SlackNotificationService $slackService;

    public function __construct(
        UnifiedAlertingService $alertingService,
        SlackNotificationService $slackService
    ) {
        $this->alertingService = $alertingService;
        $this->slackService = $slackService;
    }

    /**
     * Example: Process payment with failure tracking.
     */
    public function processPayment(Request $request)
    {
        try {
            // Simulate payment processing
            $paymentSuccessful = rand(0, 10) > 3; // 70% success rate for demo

            if (! $paymentSuccessful) {
                throw new \Exception('Payment declined by bank');
            }

            // Payment successful
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
            ]);
        } catch (\Exception $e) {
            // Track the payment failure
            $this->recordPaymentFailure(
                'stripe',
                'card_declined',
                $e->getMessage(),
                $request->input('customer_id'),
                [
                    'amount' => $request->input('amount'),
                    'currency' => 'EUR',
                ]
            );

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Example: Handle webhook with failure tracking.
     */
    public function handleWebhook(Request $request)
    {
        $provider = 'stripe';
        $eventType = $request->input('type');

        try {
            // Simulate webhook processing
            if ($eventType === 'payment_intent.failed') {
                throw new \Exception('Unable to process failed payment intent');
            }

            // Process webhook
            // ... your webhook logic here ...

            // Track success
            $this->trackWebhookSuccess($provider, $eventType);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Track webhook failure
            $this->handleWebhookFailure(
                $provider,
                $eventType,
                $e,
                $request->all()
            );

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Example: Send custom alert.
     */
    public function triggerCustomAlert(Request $request)
    {
        // Send a custom alert
        $this->alertingService->alert('custom_business_event', [
            'event' => $request->input('event'),
            'value' => $request->input('value'),
            'threshold' => $request->input('threshold'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alert triggered',
        ]);
    }

    /**
     * Example: Send Slack notification.
     */
    public function sendSlackUpdate(Request $request)
    {
        // Send system status update
        $metrics = [
            'api_success_rate' => ['value' => 98.5, 'unit' => '%', 'label' => 'API Success Rate'],
            'response_time' => ['value' => 145, 'unit' => 'ms', 'label' => 'Avg Response Time'],
            'queue_size' => ['value' => 42, 'unit' => '', 'label' => 'Queue Size'],
            'active_users' => ['value' => 1234, 'unit' => '', 'label' => 'Active Users'],
        ];

        $success = $this->slackService->sendStatusUpdate($metrics);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Slack update sent' : 'Failed to send Slack update',
        ]);
    }

    /**
     * Example: Check system health.
     */
    public function checkHealth(Request $request)
    {
        // Run health checks
        $alerts = $this->alertingService->checkSystemHealth();

        return response()->json([
            'healthy' => empty($alerts),
            'issues' => $alerts,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Example: Get alert history.
     */
    public function getAlertHistory(Request $request)
    {
        $hours = $request->input('hours', 24);
        $alerts = $this->alertingService->getActiveAlerts($hours);

        return response()->json([
            'alerts' => $alerts,
            'count' => count($alerts),
            'period_hours' => $hours,
        ]);
    }
}
