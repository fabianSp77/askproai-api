/**
 * Custom k6 Metrics for AskPro Load Testing
 */
import { Trend, Counter, Rate } from 'k6/metrics';

// Voice Hot Path Metrics (< 500ms target)
export const voiceCheckAvailability = new Trend('voice_check_availability_duration', true);
export const voiceCheckCustomer = new Trend('voice_check_customer_duration', true);
export const voiceCollectAppointment = new Trend('voice_collect_appointment_duration', true);

// Booking Flow Metrics (< 3s target)
export const bookingCreate = new Trend('booking_create_duration', true);
export const webhookProcess = new Trend('webhook_process_duration', true);

// Error tracking
export const voiceErrors = new Counter('voice_errors');
export const bookingErrors = new Counter('booking_errors');
export const webhookErrors = new Counter('webhook_errors');

// Success rates
export const voiceSuccessRate = new Rate('voice_success_rate');
export const bookingSuccessRate = new Rate('booking_success_rate');

// Queue metrics (if available via health endpoint)
export const queueDepth = new Trend('queue_depth', true);
export const queueProcessingRate = new Trend('queue_processing_rate', true);

// Helper to record metric with error handling
export function recordMetric(metric, value, success = true) {
    if (value !== null && value !== undefined) {
        metric.add(value);
    }
    return success;
}

// Summary thresholds for k6 output
export const thresholds = {
    // Voice Hot Path - CRITICAL (< 500ms p99)
    'voice_check_availability_duration': ['p(50)<200', 'p(95)<400', 'p(99)<500'],
    'voice_check_customer_duration': ['p(50)<100', 'p(95)<200', 'p(99)<300'],
    'voice_collect_appointment_duration': ['p(50)<150', 'p(95)<300', 'p(99)<400'],

    // Booking Flow (< 3s p99)
    'booking_create_duration': ['p(50)<1000', 'p(95)<2500', 'p(99)<3000'],
    'webhook_process_duration': ['p(50)<500', 'p(95)<1500', 'p(99)<2000'],

    // Error rates (< 1% for voice, < 2% for booking)
    'voice_success_rate': ['rate>0.99'],
    'booking_success_rate': ['rate>0.98'],

    // Standard HTTP metrics
    'http_req_duration': ['p(95)<1000'],
    'http_req_failed': ['rate<0.05'],
};
